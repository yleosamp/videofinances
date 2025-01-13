<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$conn = new mysqli('localhost', 'root', '', 'videofinances');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro de conexão']));
}

$month = intval($_POST['month']);
$year = intval($_POST['year']);
$user_id = $_SESSION['user_id'];
$activeTagFilters = isset($_POST['tagFilters']) ? json_decode($_POST['tagFilters']) : [];

// Criar nova planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar cabeçalhos
$sheet->setCellValue('A1', 'Nome do Vídeo');
$sheet->setCellValue('B1', 'Preço');
$sheet->setCellValue('C1', 'Moeda');
$sheet->setCellValue('D1', 'Nº de Pessoas');
$sheet->setCellValue('E1', 'Status');
$sheet->setCellValue('F1', 'Tags');
$sheet->setCellValue('G1', 'Notas');
$sheet->setCellValue('H1', 'Dia');

// Estilizar cabeçalhos
$headerStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4CAF50']
    ],
    'font' => ['color' => ['rgb' => 'FFFFFF']]
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Buscar dados com tags
$sql = "SELECT v.*, GROUP_CONCAT(t.name) as tag_names 
        FROM videos v 
        LEFT JOIN video_tags vt ON v.id = vt.video_id 
        LEFT JOIN tags t ON vt.tag_id = t.id 
        WHERE v.user_id = ? AND v.month = ? AND v.year = ?";

// Adicionar filtro de pagamento
if (isset($_POST['paymentFilter'])) {
    $paymentFilter = $_POST['paymentFilter'];
    if ($paymentFilter === 'paid') {
        $sql .= " AND v.is_paid = 1";
    } else if ($paymentFilter === 'unpaid') {
        $sql .= " AND v.is_paid = 0";
    }
}

// Adicionar filtro de tags se necessário
if (!empty($activeTagFilters)) {
    $tagPlaceholders = str_repeat('?,', count($activeTagFilters) - 1) . '?';
    $sql .= " AND v.id IN (
        SELECT video_id 
        FROM video_tags 
        WHERE tag_id IN ($tagPlaceholders)
        GROUP BY video_id 
        HAVING COUNT(DISTINCT tag_id) = ?
    )";
}

$sql .= " GROUP BY v.id ORDER BY v.order";

$stmt = $conn->prepare($sql);

if (!empty($activeTagFilters)) {
    $params = array_merge([$user_id, $month, $year], $activeTagFilters, [count($activeTagFilters)]);
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('iii', $user_id, $month, $year);
}

$stmt->execute();
$result = $stmt->get_result();

$row = 2;
$totalBRL = 0;
$totalUSD = 0;
$exchangeRate = 5;

// Tentar obter taxa de câmbio atual
try {
    $exchange = file_get_contents('https://economia.awesomeapi.com.br/last/USD-BRL');
    $exchange = json_decode($exchange, true);
    if (isset($exchange['USDBRL']['bid'])) {
        $exchangeRate = floatval($exchange['USDBRL']['bid']);
    }
} catch (Exception $e) {
    // Mantém a taxa padrão em caso de erro
}

while ($video = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $video['name']);
    $sheet->setCellValue('B' . $row, $video['price']);
    $sheet->setCellValue('C' . $row, $video['currency']);
    $sheet->setCellValue('D' . $row, $video['people_count']);
    $sheet->setCellValue('E' . $row, $video['is_paid'] ? 'Pago' : 'Não Pago');
    $sheet->setCellValue('F' . $row, $video['tag_names']);
    $sheet->setCellValue('G' . $row, $video['notes']);
    $sheet->setCellValue('H' . $row, $video['video_day']);
    
    // Calcular totais
    if ($video['currency'] === 'USD') {
        $totalUSD += floatval($video['price']);
        $totalBRL += floatval($video['price']) * $exchangeRate;
    } else {
        $totalBRL += floatval($video['price']);
        $totalUSD += floatval($video['price']) / $exchangeRate;
    }
    
    // Colorir linha baseado no status de pagamento
    $rowStyle = [
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => $video['is_paid'] ? '9FFEBB' : 'FF696C']
        ]
    ];
    $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray($rowStyle);
    
    $row++;
}

// Adicionar linhas de total (ajustando para incluir a nova coluna)
$totalRow = $row;
$sheet->setCellValue('A' . $totalRow, 'TOTAL');
$sheet->setCellValue('B' . $totalRow, number_format($totalBRL, 2));
$sheet->setCellValue('C' . $totalRow, 'BRL');
$sheet->mergeCells('D'.$totalRow.':H'.$totalRow);

$usdRow = $row + 1;
$sheet->setCellValue('A' . $usdRow, 'TOTAL');
$sheet->setCellValue('B' . $usdRow, number_format($totalUSD, 2));
$sheet->setCellValue('C' . $usdRow, 'USD');
$sheet->mergeCells('D'.$usdRow.':H'.$usdRow);

// Estilizar linhas de total
$totalStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'B974ED']
    ]
];
$sheet->getStyle('A'.$totalRow.':H'.$totalRow)->applyFromArray($totalStyle);
$sheet->getStyle('A'.$usdRow.':H'.$usdRow)->applyFromArray($totalStyle);

// Ajustar largura das colunas
foreach(range('A','H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar cabeçalhos HTTP
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="videos_' . $month . '_' . $year . '.xlsx"');
header('Cache-Control: max-age=0');

// Criar arquivo Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
