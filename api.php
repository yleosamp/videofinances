<?php
// Configurar sessão para durar o máximo possível
ini_set('session.gc_maxlifetime', 31536000); // 1 ano em segundos
ini_set('session.cookie_lifetime', 31536000);
session_set_cookie_params(31536000);

header('Content-Type: application/json');
session_start();

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'videofinances');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
    exit;
}

// Pegar a ação da requisição (pode vir tanto via POST quanto via JSON)
$action = '';
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    $action = $requestData['action'] ?? '';
} else {
    $action = $_POST['action'] ?? '';
}

// Verificar autenticação (exceto para login e registro)
if (!in_array($action, ['login', 'register']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_POST['action'] === 'delete_video') {
    if (!isset($_POST['video_id'])) {
        die(json_encode(['success' => false, 'message' => 'ID do vídeo não fornecido']));
    }
    
    $video_id = intval($_POST['video_id']);
    
    // Verificar se o vídeo pertence ao usuário
    $stmt = $conn->prepare("SELECT user_id FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    
    if (!$video || $video['user_id'] != $_SESSION['user_id']) {
        die(json_encode(['success' => false, 'message' => 'Vídeo não encontrado']));
    }
    
    // Excluir o vídeo
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir o vídeo']);
    }
    exit;
}

switch ($action) {
    case 'login':
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT id, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Senha incorreta']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        }
        break;

    case 'register':
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
        if ($conn->query($sql)) {
            $_SESSION['user_id'] = $conn->insert_id;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar']);
        }
        break;

    case 'check_auth':
        if (isset($_SESSION['user_id'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'add_video':
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';
        $currency = $_POST['currency'] ?? 'BRL';
        $month = $_POST['month'] ?? '';
        $year = $_POST['year'] ?? '';
        $user_id = $_SESSION['user_id'];
        
        // Primeiro, vamos verificar se a query está correta
        if ($stmt = $conn->prepare("INSERT INTO videos (user_id, name, price, currency, month, year) VALUES (?, ?, ?, ?, ?, ?)")) {
            $stmt->bind_param('isdsss', $user_id, $name, $price, $currency, $month, $year);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao executar query: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $conn->error]);
        }
        break;

    case 'get_videos':
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        $user_id = $_SESSION['user_id'];
        
        $sql = "SELECT v.*, 
                GROUP_CONCAT(DISTINCT vt.tag_id) as tag_ids
                FROM videos v 
                LEFT JOIN video_tags vt ON v.id = vt.video_id
                WHERE v.user_id = ? AND v.month = ? AND v.year = ?
                GROUP BY v.id
                ORDER BY v.`order`";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            // Converte a string de IDs de tags em um array
            $tagIds = $row['tag_ids'] ? explode(',', $row['tag_ids']) : [];
            $row['tags'] = array_map('intval', $tagIds);
            unset($row['tag_ids']);
            $videos[] = $row;
        }
        
        echo json_encode(['success' => true, 'videos' => $videos]);
        break;

    case 'toggle_payment':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        
        $sql = "UPDATE videos SET is_paid = NOT is_paid 
                WHERE id = $video_id AND user_id = $user_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
        }
        break;

    case 'get_notes':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        
        $sql = "SELECT notes FROM videos WHERE id = $video_id AND user_id = $user_id";
        $result = $conn->query($sql);
        $video = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'notes' => $video['notes']]);
        break;

    case 'save_notes':
        $videoId = $_POST['video_id'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $name = $_POST['name'] ?? '';
        $day = $_POST['day'] ?? null;
        $brlPrice = $_POST['brl_price'] ?? '';
        $usdPrice = $_POST['usd_price'] ?? '';
        
        $sql = "UPDATE videos SET notes = ?, name = ?, video_day = ?, price = ?, currency = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // Define o preço e moeda baseado na moeda atual do vídeo
        $currentVideo = $conn->query("SELECT currency FROM videos WHERE id = $videoId")->fetch_assoc();
        $price = $currentVideo['currency'] === 'BRL' ? $brlPrice : $usdPrice;
        
        $stmt->bind_param('ssidsi', $notes, $name, $day, $price, $currentVideo['currency'], $videoId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar notas']);
        }
        break;

    case 'update_currency':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        $currency = $_POST['currency'];
        $price = floatval($_POST['price']);
        
        $sql = "UPDATE videos SET currency = '$currency', price = $price 
                WHERE id = $video_id AND user_id = $user_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar moeda']);
        }
        break;

    case 'save_video_details':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        $notes = $conn->real_escape_string($_POST['notes']);
        $name = $conn->real_escape_string($_POST['name']);
        $price = floatval($_POST['price']);
        $currency = $_POST['currency'];
        $people_count = intval($_POST['people_count']);
        
        $sql = "UPDATE videos SET 
                notes = '$notes',
                name = '$name',
                price = $price,
                currency = '$currency',
                people_count = $people_count
                WHERE id = $video_id AND user_id = $user_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar alterações']);
        }
        break;

    case 'update_order':
        $orders = json_decode($_POST['orders'], true);
        $success = true;
        
        foreach ($orders as $item) {
            $id = intval($item['id']);
            $order = intval($item['order']);
            $user_id = $_SESSION['user_id'];
            
            $sql = "UPDATE videos SET `order` = $order 
                    WHERE id = $id AND user_id = $user_id";
            
            if (!$conn->query($sql)) {
                $success = false;
                break;
            }
        }
        
        echo json_encode(['success' => $success]);
        break;

    case 'get_tags':
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM tags WHERE user_id = $user_id ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        
        echo json_encode(['success' => true, 'tags' => $tags]);
        break;

    case 'create_tag':
        $user_id = $_SESSION['user_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $color = $conn->real_escape_string($_POST['color']);
        
        $sql = "INSERT INTO tags (user_id, name, color) VALUES ($user_id, '$name', '$color')";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar tag']);
        }
        break;

    case 'delete_tag':
        $tag_id = intval($_POST['tag_id']);
        $user_id = $_SESSION['user_id'];
        
        $sql = "DELETE FROM tags WHERE id = $tag_id AND user_id = $user_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir tag']);
        }
        break;

    case 'get_video_tags':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        
        // Buscar todas as tags do usuário
        $sql = "SELECT * FROM tags WHERE user_id = $user_id ORDER BY name";
        $result = $conn->query($sql);
        $allTags = [];
        while ($row = $result->fetch_assoc()) {
            $allTags[] = $row;
        }
        
        // Buscar tags selecionadas para este vídeo
        $sql = "SELECT t.* FROM tags t 
                JOIN video_tags vt ON t.id = vt.tag_id 
                WHERE vt.video_id = $video_id";
        $result = $conn->query($sql);
        $selectedTags = [];
        while ($row = $result->fetch_assoc()) {
            $selectedTags[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'allTags' => $allTags,
            'selectedTags' => $selectedTags
        ]);
        break;

    case 'save_video_tags':
        $video_id = intval($_POST['video_id']);
        $user_id = $_SESSION['user_id'];
        $tags = json_decode($_POST['tags']);
        
        $conn->begin_transaction();
        try {
            // Primeiro, remove todas as tags existentes
            $sql = "DELETE FROM video_tags WHERE video_id = $video_id";
            $conn->query($sql);
            
            // Depois, adiciona as novas tags selecionadas
            if (!empty($tags)) {
                foreach ($tags as $tag_id) {
                    $sql = "INSERT INTO video_tags (video_id, tag_id) VALUES ($video_id, $tag_id)";
                    $conn->query($sql);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'createTag':
        try {
            if (!isset($_POST['name']) || !isset($_POST['color'])) {
                throw new Exception('Dados incompletos');
            }

            $name = $conn->real_escape_string($_POST['name']);
            $color = $conn->real_escape_string($_POST['color']);
            $user_id = $_SESSION['user_id'];

            // Verificar se a tag já existe para este usuário
            $checkSql = "SELECT id FROM tags WHERE name = ? AND user_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('si', $name, $user_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Tag já existe']);
                exit;
            }

            $sql = "INSERT INTO tags (name, color, user_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $name, $color, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tag criada com sucesso',
                    'tagId' => $stmt->insert_id
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erro ao criar tag: ' . $conn->error
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ]);
        }
        break;
}

$conn->close();

