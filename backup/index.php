<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Vídeos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="icon/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #121212;
            color: #E0E0E0;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        select, input, button {
            outline: none;
            transition: all 0.2s ease;
        }
        .price-tag {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .price-usd {
            background-color: #2D7D4C;
            color: #9FFEBB;
        }
        .price-brl {
            background-color: #3E62A5;
            color: #ADC8FB;
        }
        .price-total {
            background-color: #6B3A9D;
            color: #D4B6FF;
        }
        .card {
            background: #1E1E1E;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .btn {
            @apply px-6 py-3 rounded-full font-medium transition-all duration-200 shadow-md hover:shadow-lg active:shadow-sm;
        }
        .btn-primary {
            @apply bg-[#3E62A5] text-[#ADC8FB] hover:bg-[#3E62A5]/90 active:bg-[#3E62A5]/80;
        }
        .btn-secondary {
            @apply bg-[#2A2A2A] text-gray-300 hover:bg-[#333333] active:bg-[#292929] border border-gray-700;
        }
        .input-field {
            @apply bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-gray-100 focus:border-purple-500 transition-all duration-200;
        }
        .select-field {
            @apply bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-gray-100 appearance-none cursor-pointer hover:border-gray-600 focus:border-purple-500;
        }
        #addVideoButton {
            @apply bg-[#3E62A5] text-[#ADC8FB] hover:bg-[#3E62A5]/90 transition-all shadow-lg hover:shadow-[#3E62A5]/20;
        }
        #openTagsButton, #currencyToggle {
            @apply bg-[#2A2A2A] text-gray-300 hover:bg-[#333333] border border-gray-700 transition-all;
        }
        #exportExcelButton {
            @apply bg-[#2D7D4C] text-[#9FFEBB] hover:bg-[#2D7D4C]/90 transition-all shadow-lg hover:shadow-[#2D7D4C]/20;
        }
        .video-card {
            @apply bg-gray-800/50 backdrop-blur-sm border border-gray-700/50 
                   rounded-xl p-4 flex items-center gap-4 
                   transition-all duration-200
                   hover:border-[#3E62A5] hover:bg-gray-800/70
                   hover:shadow-lg hover:shadow-[#3E62A5]/10;
        }
        
        .video-card:hover {
            @apply border-[#3E62A5] shadow-lg shadow-[#3E62A5]/10 transform -translate-y-0.5;
        }
        
        .video-info {
            @apply flex-1 cursor-pointer;
        }
        
        .video-title {
            @apply text-gray-100 font-medium mb-1;
        }
        
        .video-tags {
            @apply flex flex-wrap gap-1.5 mt-2;
        }
        
        .video-tag {
            @apply px-2.5 py-1 rounded-full text-xs font-medium
                   shadow-sm transition-transform hover:scale-105;
        }
        
        .video-actions {
            @apply flex items-center gap-3;
        }
        
        .payment-toggle {
            @apply transition-all duration-200;
        }
        
        .payment-toggle.paid {
            @apply bg-[#2D7D4C]/20 text-[#9FFEBB] border-2 border-[#2D7D4C]
                   hover:bg-[#2D7D4C]/30;
        }
        
        .payment-toggle.unpaid {
            @apply bg-gray-700/20 text-gray-400 border-2 border-gray-600
                   hover:bg-gray-700/30 hover:border-gray-500;
        }
        
        .drag-handle {
            @apply text-gray-500 hover:text-gray-400 cursor-move p-2;
        }

        .price-tag {
            @apply py-2 px-4 rounded-xl font-medium transition-all duration-200;
        }
    </style>
    <script>
        function switchUI(version) {
            localStorage.setItem('uiVersion', version);
            window.location.href = version === 'new' ? 'index.php' : 'originalUi.php';
        }

        function handleAuth() {
            const loginButton = document.getElementById('loginButton');
            if (loginButton.textContent === 'Sair') {
                handleLogout();
            } else {
                document.getElementById('authModal').classList.add('active');
            }
        }

        function handleLogout() {
            const formData = new FormData();
            formData.append('action', 'logout');
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(() => location.reload())
            .catch(error => console.error('Erro ao fazer logout:', error));
        }
    </script>
</head>
<body class="min-h-screen flex flex-col p-8">
    <!-- Botões de navegação -->
    <div class="self-end mb-8 flex items-center gap-4">
        <button onclick="switchUI('old')" class="bg-[#2A2A2A] text-gray-300 px-6 py-2.5 rounded-full font-medium transition-all hover:bg-[#333333] border border-gray-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L4.414 9H17a1 1 0 110 2H4.414l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            UI Original
        </button>
        <button id="loginButton" onclick="handleAuth()" class="bg-[#2A2A2A] text-gray-300 px-6 py-2.5 rounded-full font-medium transition-all hover:bg-[#333333] border border-gray-700 ">
            Login / Registro
        </button>
    </div>

    <div class="w-full max-w-xl mx-auto space-y-6">
        <!-- Seletor Mês, Ano e Pessoas -->
        <div class="grid grid-cols-[1fr,auto,auto] gap-4">
            <select id="monthSelect" class="bg-[#151515] border border-[#313131] text-gray-100 px-6 py-3.5 rounded-xl  hover:border-[#505050] focus:border-purple-500 transition-all cursor-pointer appearance-none">
                <option value="1">Janeiro</option>
                <option value="2">Fevereiro</option>
                <option value="3">Março</option>
                <option value="4">Abril</option>
                <option value="5">Maio</option>
                <option value="6">Junho</option>
                <option value="7">Julho</option>
                <option value="8">Agosto</option>
                <option value="9">Setembro</option>
                <option value="10">Outubro</option>
                <option value="11">Novembro</option>
                <option value="12">Dezembro</option>
            </select>
            
            <select id="yearSelect" class="bg-[#151515] border border-[#313131] text-gray-100 px-6 py-3.5 rounded-xl hover:border-[#505050] focus:border-purple-500 transition-all cursor-pointer appearance-none w-32">
                <!-- Anos via JavaScript -->
            </select>
            
            <div id="peopleCounter" class="bg-[#151515] border border-[#313131] rounded-xl p-3.5 cursor-pointer hover:bg-gray-700 transition-all border border-gray-700 hover:border-[#505050]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
            </div>
        </div>

        <!-- Inputs de Nome e Tags -->
        <div class="grid grid-cols-[1fr,auto] gap-3">
            <input type="text" id="videoName" placeholder="Nome do vídeo" class="bg-[#151515] border border-[#313131] text-gray-100 px-6 py-3.5 rounded-xl hover:border-[#505050] focus:border-purple-500 transition-all placeholder-gray-500">
            <button id="openTagsButton" class="bg-purple-600 text-white px-6 py-3.5 rounded-xl hover:border-[#505050] transition-all font-medium">
                Tags
            </button>
        </div>

        <!-- Input de Preço -->
        <div class="flex items-center">
            <input type="text" id="videoPrice" placeholder="0" class="bg-[#151515] border border-[#313131] text-gray-100 px-6 py-2.5 rounded-l-xl hover:border-[#505050] focus:border-purple-500 transition-all w-32 text-center placeholder-gray-500 flex-shrink-0">
            <button id="currencyToggle" class="bg-[#3E62A5] text-white px-6 py-2.5 rounded-r-xl font-medium min-w-[60px] hover:border-[#505050] transition-all flex-shrink-0">
                U$
            </button>
        </div>

        <!-- Botão Adicionar -->
        <button id="addVideoButton" class="w-full bg-purple-600 text-white py-4 rounded-xl text-lg font-medium hover:border-[#505050] transition-all shadow-lg hover:shadow-purple-500/20">
            Adicionar
        </button>

        <!-- Lista de Vídeos -->
        <div id="videosList" class="pt-8 space-y-4 hidden">
            <h2 id="monthTitle" class="text-2xl px-2"><strong>Janeiro</strong></h2>
            
            <div id="videosContainer" class="space-y-2">
                <!-- Os vídeos serão inseridos aqui dinamicamente -->
            </div>
            
            <!-- Linha separadora -->
            <div class="h-px bg-[#313131]"></div>
            
            <!-- Total -->
            <div class="flex items-center justify-between  px-6 py-3 rounded-md bg-[#151515] rounded-xl border border-[#313131]">
                <span><strong>TOTAL</strong></span>
                <div class="flex items-center gap-3">
                    <span id="totalValue" class="price-tag price-total" onclick="toggleTotalCurrency()">R$ 0</span>
                    <button id="totalToggle" class="w-8 h-8 bg-[#B974ED] rounded-md" onclick="toggleTotalView()"></button>
                </div>
            </div>

            <!-- Container de Tags para Filtros -->
            <div class="flex flex-wrap gap-2 mt-4 " id="filterTags">
                <!-- Tags serão inseridas aqui -->
            </div>
        </div>

        <!-- Botão de Exportar -->
        <div class="mt-4">
            <button id="exportExcelButton" class="w-full bg-[#4CAF50] text-white py-3 rounded-md text-lg font-medium hover:bg-[#4CAF50]/90 transition-colors">
                Exportar para Excel
            </button>
        </div>
    </div>

    <!-- Modal de Notas -->
    <div id="notesModal" class="modal">
        <div class="bg-[#313131] rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl">Editar vídeo</h3>
                <button onclick="deleteVideo()" class="bg-[#FF696C] text-white px-4 py-2 rounded-md hover:bg-[#FF696C]/90 transition-colors">
                    Excluir
                </button>
            </div>
            
            <!-- Campo Nome -->
            <div class="mb-4">
                <label class="text-sm text-gray-400 block mb-2">Nome do vídeo</label>
                <input type="text" id="modalVideoName" class="w-full bg-[#202020] rounded-md p-4" placeholder="Nome do vídeo">
            </div>
            
            <!-- Campos de Valor -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-sm text-gray-400 block mb-2">Valor em Real</label>
                    <input type="number" step="0.01" id="modalBrlPrice" class="w-full bg-[#202020] rounded-md p-4" 
                           placeholder="R$ 0.00" oninput="updateUsdFromBrl()">
                </div>
                <div>
                    <label class="text-sm text-gray-400 block mb-2">Valor em Dólar</label>
                    <input type="number" step="0.01" id="modalUsdPrice" class="w-full bg-[#202020] rounded-md p-4" 
                           placeholder="$ 0.00" oninput="updateBrlFromUsd()">
                </div>
            </div>
            
            <!-- Campo Notas -->
            <div class="mb-4">
                <label class="text-sm text-gray-400 block mb-2">Notas</label>
                <textarea id="videoNotes" class="w-full h-32 bg-[#202020] rounded-md p-4"></textarea>
            </div>
            
            <!-- Pessoas -->
            <div class="flex items-center gap-3 bg-[#202020] p-3 rounded-md mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <input id="modalPeopleCount" type="number" min="1" value="1" class="w-12 bg-transparent text-center">
            </div>
            
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm text-gray-400">Tags</label>
                    <button onclick="openVideoTagsModal()" class="text-[#ADC8FB] text-sm hover:text-[#ADC8FB]/80">
                        Gerenciar Tags
                    </button>
                </div>
                <div id="videoTagsList" class="flex flex-wrap gap-2">
                    <!-- Tags do vídeo serão inseridas aqui -->
                </div>
            </div>
            
            <!-- <div class="mb-4">
                <label class="text-sm text-gray-400 block mb-2">Data do vídeo</label>
                <input type="date" id="modalVideoDate" class="w-full bg-[#202020] rounded-md p-4 text-white">
            </div> -->
            
            <div class="mb-4">
                <label class="text-sm text-gray-400 block mb-2">Dia do vídeo</label>
                <div class="flex gap-2 items-center">
                    <input type="number" id="modalVideoDay" min="1" max="31" class="w-24 bg-[#202020] rounded-md p-4" placeholder="Dia">
                    <span class="text-gray-400" id="modalVideoMonthYear"></span>
                </div>
            </div>
            
            <input type="hidden" id="currentVideoId">
            <button onclick="saveNotes()" class="w-full bg-[#3E62A5] text-[#ADC8FB] py-2 rounded-md text-lg font-medium">
                Salvar
            </button>
        </div>
    </div>

    <!-- Modal de Autenticação -->
    <div id="authModal" class="modal">
        <div class="bg-[#313131] rounded-lg p-6 w-full max-w-md mx-4">
            <h3 id="authTitle" class="text-xl mb-4">Login</h3>
            <form id="authForm" class="space-y-4">
                <input type="email" placeholder="Email" class="w-full bg-[#202020] rounded-md px-4 py-2">
                <input type="password" placeholder="Senha" class="w-full bg-[#202020] rounded-md px-4 py-2">
                <button type="submit" class="w-full bg-[#3E62A5] text-[#ADC8FB] py-2 rounded-md">Entrar</button>
                <a href="#" id="toggleAuthMode" class="block text-center text-sm text-[#ADC8FB]">Criar uma conta</a>
            </form>
        </div>
    </div>

    <!-- Modal de Tags -->
    <div id="tagsModal" class="modal">
        <div class="bg-[#313131] rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl">Gerenciar Tags</h3>
                <button onclick="closeTagsModal()" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Criar nova tag -->
            <div class="mb-4">
                <div class="flex gap-2 mb-2">
                    <input type="text" id="newTagName" placeholder="Nova tag" class="flex-1 bg-[#202020] rounded-md px-4 py-2">
                    <input type="color" id="newTagColor" class="bg-[#202020] rounded-md p-1 h-10 w-[30px]" value="#3E62A5">
                    <button onclick="createTag()" class="bg-[#3E62A5] text-[#ADC8FB] px-4 rounded-md">
                        Adicionar
                    </button>
                </div>
            </div>

            <!-- Lista de tags -->
            <div id="tagsList" class="space-y-2 max-h-60 overflow-y-auto mb-4">
                <!-- Tags serão inseridas aqui -->
            </div>

            <!-- Botão Salvar -->
            <button onclick="saveTagSelections()" class="w-full bg-[#3E62A5] text-[#ADC8FB] py-2 rounded-md">
                Salvar Seleção
            </button>
        </div>
    </div>

    <!-- Modal de Tags do Vídeo -->
    <div id="videoTagsModal" class="modal">
        <div class="bg-[#313131] rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl">Tags do Vídeo</h3>
                <button onclick="closeVideoTagsModal()" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div id="videoTagsSelection" class="space-y-2 max-h-60 overflow-y-auto">
                <!-- Tags disponíveis serão inseridas aqui -->
            </div>
            
            <button onclick="saveVideoTags()" class="w-full bg-[#3E62A5] text-[#ADC8FB] py-2 rounded-md mt-4">
                Salvar Tags
            </button>
        </div>
    </div>

    <!-- Template para o card de vídeo -->
    <template id="videoTemplate">
        <div class="video-card group">
            <!-- Alça de arrasto -->
            <div class="drag-handle p-2 text-gray-500 hover:text-gray-400 cursor-move">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
            </div>

            <!-- Informações do vídeo -->
            <div class="flex-1 space-y-2">
                <h3 class="text-gray-100 font-medium text-lg group-hover:text-[#ADC8FB] transition-colors cursor-pointer"></h3>
                <div class="video-tags flex flex-wrap gap-1.5"></div>
            </div>

            <!-- Ações do vídeo -->
            <div class="flex items-center gap-4">
                <!-- Status de pagamento -->
                <button class="payment-toggle w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>

                <!-- Valor -->
                <span class="price-tag min-w-[100px] text-center cursor-pointer"></span>
            </div>
        </div>
    </template>

    <script>
        let exchangeRate = 5; // Taxa padrão para fallback
        let isLoginMode = true;
        let totalViewState = 'all'; // 'all', 'paid', 'unpaid'
        const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        let selectedTags = new Set();
        let tempSelectedTags = new Set();
        let allTags = [];
        let activeTagFilters = new Set();
        let isLoadingVideos = false;
        let selectedVideoTags = new Set();
        let tempVideoTags = new Set();

        function switchUI(version) {
            // Salva a preferência do usuário
            localStorage.setItem('uiVersion', version);
            
            // Redireciona para a UI apropriada
            if (version === 'new') {
                window.location.href = 'index.php';
            } else {
                window.location.href = 'originalUi.php';
            }
        }

        function handleAuth() {
            if (loginButton.textContent === 'Sair') {
                handleLogout();
            } else {
                document.getElementById('authModal').classList.add('active');
            }
        }

        async function handleAuth(e) {
            if (e && e.preventDefault) {
                e.preventDefault();
            }
            
            // Se for um clique no botão (sem evento de form)
            if (!e || !e.target || e.target.tagName !== 'FORM') {
                if (loginButton.textContent === 'Sair') {
                    handleLogout();
                } else {
                    document.getElementById('authModal').classList.add('active');
                }
                return;
            }

            // Se chegou aqui, é um submit do formulário
            const email = document.querySelector('#authForm input[type="email"]').value;
            const password = document.querySelector('#authForm input[type="password"]').value;

            const formData = new FormData();
            formData.append('action', isLoginMode ? 'login' : 'register');
            formData.append('email', email);
            formData.append('password', password);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    authModal.classList.remove('active');
                    document.getElementById('videosList').classList.remove('hidden');
                    loginButton.textContent = 'Sair';
                    
                    const currentYear = new Date().getFullYear();
                    const currentMonth = "1";
                    
                    // Atualizar os selects
                    document.getElementById('yearSelect').value = currentYear;
                    document.getElementById('monthSelect').value = currentMonth;
                    
                    // Atualizar título
                    updateMonthTitle(currentMonth, currentYear);
                    
                    // Carregar as tags
                    await loadTags();
                    
                    // Carregar os vídeos
                    await loadVideos(currentMonth, currentYear);
                    
                    if (!isLoginMode) {
                        const loginFormData = new FormData();
                        loginFormData.append('action', 'login');
                        loginFormData.append('email', email);
                        loginFormData.append('password', password);
                        await fetch('api.php', {
                            method: 'POST',
                            body: loginFormData
                        });
                    }
                } else {
                    alert(data.message || 'Erro na autenticação');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro na autenticação');
            }
        }

        async function handleLogout() {
            const formData = new FormData();
            formData.append('action', 'logout');
            try {
                await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                location.reload();
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            }
        }

        async function getCurrencyRates() {
            try {
                const response = await fetch('https://economia.awesomeapi.com.br/last/USD-BRL,BRL-USD');
                const data = await response.json();
                
                // Atualiza a taxa de câmbio
                exchangeRate = parseFloat(data.USDBRL.bid);
                
                return {
                    USD_BRL: exchangeRate,
                    BRL_USD: parseFloat(data.BRLUSD.bid)
                };
            } catch (error) {
                console.error('Erro ao obter taxas de câmbio:', error);
                
                // Retorna valores padrão caso ocorra um erro
                return {
                    USD_BRL: exchangeRate, // Mantém o valor padrão de fallback
                    BRL_USD: 1 / exchangeRate // Usa o inverso para a taxa BRL para USD
                };
            }
        }

        // Exemplo de uso
        getCurrencyRates().then(rates => {
            console.log('Taxas de câmbio:', rates);
        });


        async function updateExchangeRates() {
            const rates = await getCurrencyRates();
            exchangeRate = rates.USD_BRL;
        }

        async function checkAuth() {
            if (isLoadingVideos) return;
            isLoadingVideos = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'check_auth');
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loginButton.textContent = 'Sair';
                    document.getElementById('videosList').classList.remove('hidden');
                    
                    const currentYear = new Date().getFullYear();
                    const currentMonth = "1";
                    
                    yearSelect.value = currentYear;
                    monthSelect.value = currentMonth;
                    
                    await loadTags();
                    updateMonthTitle(currentMonth, currentYear);
                    await loadVideos(currentMonth, currentYear);
                } else {
                    loginButton.textContent = 'Login / Registro';
                    document.getElementById('videosList').classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro na autenticação:', error);
            } finally {
                isLoadingVideos = false;
            }
        }

        async function addVideo() {
            const name = document.getElementById('videoName').value;
            const price = document.getElementById('videoPrice').value;
            const currency = document.getElementById('currencyToggle').textContent;
            const selectedMonth = document.getElementById('monthSelect').value;
            const selectedYear = document.getElementById('yearSelect').value;
            
            if (!name || !price) {
                alert('Por favor, preencha todos os campos');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add_video');
            formData.append('name', name);
            formData.append('price', price);
            formData.append('currency', currency === 'R$' ? 'BRL' : 'USD');
            formData.append('month', selectedMonth);
            formData.append('year', selectedYear);
            
            // Adiciona as tags selecionadas
            const tagsArray = Array.from(selectedTags).map(tagId => ({
                id: tagId,
                name: allTags.find(t => t.id === tagId)?.name || '',
                color: allTags.find(t => t.id === tagId)?.color || ''
            }));
            formData.append('tags', JSON.stringify(tagsArray));
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('videoName').value = '';
                    document.getElementById('videoPrice').value = '';
                    selectedTags.clear();
                    tempSelectedTags.clear();
                    updateSelectedTagsDisplay();
                    await loadVideos(selectedMonth, selectedYear);
                    document.getElementById('videosList').classList.remove('hidden');
                } else {
                    throw new Error(data.message || 'Erro ao adicionar vídeo');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao adicionar vídeo. Por favor, tente novamente.');
            }
        }

        async function loadVideos(month, year) {
            const container = document.getElementById('videosContainer');
            const formData = new FormData();
            formData.append('action', 'get_videos');
            formData.append('month', month);
            formData.append('year', year);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    let html = '';
                    let totalBRL = 0;
                    let totalUSD = 0;

                    // Ordenar vídeos pelo campo order antes de filtrar
                    data.videos.sort((a, b) => parseInt(a.order) - parseInt(b.order));

                    const filteredVideos = data.videos.filter(video => {
                        if (totalViewState === 'paid') return parseInt(video.is_paid) === 1;
                        if (totalViewState === 'unpaid') return parseInt(video.is_paid) === 0;
                        return true;
                    });

                    filteredVideos.forEach(video => {
                        const price = parseFloat(video.price) || 0;
                        if (video.currency === 'USD') {
                            totalUSD += price;
                            totalBRL += price * exchangeRate;
                        } else {
                            totalBRL += price;
                            totalUSD += price / exchangeRate;
                        }

                        html += `
                            <div class="flex items-center group" 
                                 draggable="true" 
                                 data-video-id="${video.id}" 
                                 data-order="${video.order}"
                                 data-paid="${video.is_paid}"
                                 data-price="${video.price}"
                                 data-currency="${video.currency}"
                                 data-tags='${JSON.stringify(video.tags || [])}'>
                                <div class="text-gray-500 hover:text-gray-400 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity mr-[-20px] z-10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8" />
                                    </svg>
                                </div>
                                <div class="flex items-center justify-between bg-[#151515] rounded-xl border border-[#313131] px-6 py-3 rounded-md w-full">
                                    <div class="flex items-center gap-4 flex-1">
                                        <span class="cursor-pointer flex-1" 
                                              onclick="showNotes(${video.id}, '${video.notes || ''}', '${video.name}', ${video.price}, '${video.currency}', ${video.video_day})">${video.name}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="price-tag ${video.currency === 'USD' ? 'price-usd' : 'price-brl'}" 
                                              onclick="toggleCurrency(this, ${video.id})">
                                            ${video.currency === 'USD' ? 'U$' : 'R$'} ${price.toFixed(2)}
                                        </span>
                                        <button onclick="togglePaymentStatus(this, ${video.id})" 
                                                class="w-8 h-8 ${parseInt(video.is_paid) === 1 ? 'bg-[#9FFEBB]' : 'bg-[#FF696C]'} rounded-md">
                                        </button>
                                    </div>
                                </div>
                            </div>`;
                    });

                    container.innerHTML = html;
                    updateTotal(totalBRL, totalUSD);
                    initDragAndDrop();
                    filterVideos(); // Aplica os filtros após carregar os vídeos
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function updateTotal(totalBRL, totalUSD) {
            const totalElement = document.getElementById('totalValue');
            const currentCurrency = totalElement.textContent.includes('R$') ? 'BRL' : 'USD';
            
            totalElement.textContent = currentCurrency === 'BRL' 
                ? `R$ ${totalBRL.toFixed(2)}` 
                : `U$ ${totalUSD.toFixed(2)}`;
        }

        function toggleTotalCurrency() {
            const totalElement = document.getElementById('totalValue');
            const isUSD = totalElement.textContent.includes('U$');
            const value = parseFloat(totalElement.textContent.split(' ')[1]);
            
            if (isUSD) {
                totalElement.textContent = `R$ ${(value * exchangeRate).toFixed(2)}`;
                totalElement.classList.remove('price-usd');
                totalElement.classList.add('price-brl');
            } else {
                totalElement.textContent = `U$ ${(value / exchangeRate).toFixed(2)}`;
                totalElement.classList.remove('price-brl');
                totalElement.classList.add('price-usd');
            }
        }

        // Event Listeners
        document.getElementById('addVideoButton').addEventListener('click', addVideo);
        document.getElementById('peopleCounter').addEventListener('click', function() {
            this.innerHTML = '<input type="number" min="1" value="1" class="w-12 bg-transparent text-center">';
            this.querySelector('input').focus();
        });

        document.getElementById('currencyToggle').addEventListener('click', function() {
            this.textContent = this.textContent === 'R$' ? 'U$' : 'R$';
            if (this.textContent === 'R$') {
                this.classList.remove('bg-[#3E62A5]');
                this.classList.add('bg-[#2D7D4C]');
            } else {
                this.classList.remove('bg-[#2D7D4C]');
                this.classList.add('bg-[#3E62A5]');
            }
        });

        // Configurar anos no select e inicializar página
        document.addEventListener('DOMContentLoaded', async function() {
            await checkAuth();
            
            // Configurar anos no select
            const yearSelect = document.getElementById('yearSelect');
            const monthSelect = document.getElementById('monthSelect');
            const currentYear = new Date().getFullYear();
            const years = new Set([2024, currentYear, currentYear + 1]);
            
            yearSelect.innerHTML = '';
            Array.from(years).sort().forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            });
            
            yearSelect.value = currentYear;
            monthSelect.value = "1"; // Janeiro
            
            yearSelect.addEventListener('change', function() {
                updateMonthTitle(monthSelect.value, this.value);
                loadVideos(monthSelect.value, this.value);
            });
            
            monthSelect.addEventListener('change', function() {
                updateMonthTitle(this.value, yearSelect.value);
                loadVideos(this.value, yearSelect.value);
            });

            // Event listeners para tags
            document.getElementById('openTagsButton').addEventListener('click', () => {
                tempSelectedTags = new Set(selectedTags);
                document.getElementById('tagsModal').classList.add('active');
                loadTags();
            });

            document.getElementById('tagsModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('tagsModal')) {
                    closeTagsModal();
                }
            });

            updateMonthTitle(1, currentYear);
            await loadVideos(1, currentYear);
        });

        function updateMonthTitle(month, year) {
            const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            const monthTitle = document.getElementById('monthTitle');
            monthTitle.innerHTML = `<strong>${months[month - 1]} ${year}</strong>`;
        }

        // Auth Modal
        const loginButton = document.getElementById('loginButton');
        const authModal = document.getElementById('authModal');
        const authTitle = document.getElementById('authTitle');
        const authForm = document.getElementById('authForm');
        const toggleAuthMode = document.getElementById('toggleAuthMode');

        loginButton.addEventListener('click', handleAuth);

        authModal.addEventListener('click', (e) => {
            if (e.target === authModal) {
                authModal.classList.remove('active');
            }
        });

        toggleAuthMode.addEventListener('click', (e) => {
            e.preventDefault();
            isLoginMode = !isLoginMode;
            authTitle.textContent = isLoginMode ? 'Login' : 'Registro';
            authForm.querySelector('button').textContent = isLoginMode ? 'Entrar' : 'Registrar';
            toggleAuthMode.textContent = isLoginMode ? 'Criar uma conta' : 'Já tenho uma conta';
        });

        authForm.addEventListener('submit', handleAuth);

        // Funções para o modal e manipulação de vídeos
        async function openModal(videoId, videoName, price, currency, people) {
            const modal = document.getElementById('notesModal');
            const modalPrice = document.getElementById('modalPrice');
            const modalPeopleCount = document.getElementById('modalPeopleCount');
            const notesTextarea = document.getElementById('videoNotes');
            const currentVideoId = document.getElementById('currentVideoId');
            
            currentVideoId.value = videoId;
            
            // Buscar notas do vídeo
            const formData = new FormData();
            formData.append('action', 'get_notes');
            formData.append('video_id', videoId);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    notesTextarea.value = data.notes || '';
                }
            } catch (error) {
                console.error('Erro:', error);
            }
            
            // Configurar preço e pessoas
            modalPrice.textContent = `${currency === 'USD' ? 'U$' : 'R$'} ${(price / people).toFixed(2)}`;
            modalPrice.className = `price-tag ${currency === 'USD' ? 'price-usd' : 'price-brl'}`;
            modalPeopleCount.value = people;
            modalPeopleCount.dataset.originalPrice = price;
            modalPeopleCount.dataset.currency = currency;
            
            modal.classList.add('active');
        }

        function updateModalPrice() {
            const modalPrice = document.getElementById('modalPrice');
            const modalPeopleCount = document.getElementById('modalPeopleCount');
            const originalPrice = parseFloat(modalPeopleCount.dataset.originalPrice);
            const currency = modalPeopleCount.dataset.currency;
            const people = parseInt(modalPeopleCount.value);
            
            const newPrice = originalPrice / people;
            modalPrice.textContent = `${currency === 'USD' ? 'U$' : 'R$'} ${newPrice.toFixed(2)}`;
        }

        async function saveNotes() {
            const videoId = document.getElementById('currentVideoId').value;
            const notes = document.getElementById('videoNotes').value;
            const name = document.getElementById('modalVideoName').value;
            const day = document.getElementById('modalVideoDay').value;
            const brlPrice = document.getElementById('modalBrlPrice').value;
            const usdPrice = document.getElementById('modalUsdPrice').value;
            
            const formData = new FormData();
            formData.append('action', 'save_notes');
            formData.append('video_id', videoId);
            formData.append('notes', notes);
            formData.append('name', name);
            formData.append('day', day);
            formData.append('brl_price', brlPrice);
            formData.append('usd_price', usdPrice);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('notesModal').classList.remove('active');
                    const month = document.getElementById('monthSelect').value;
                    const year = document.getElementById('yearSelect').value;
                    await loadVideos(month, year);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar notas. Por favor, tente novamente.');
            }
        }

        async function toggleCurrency(element, videoId) {
            const currentText = element.textContent.trim();
            const currentValue = parseFloat(currentText.split(' ')[1]);
            const isCurrentlyUSD = currentText.includes('U$');
            const currentMonth = document.getElementById('monthSelect').value;
            const currentYear = document.getElementById('yearSelect').value;
            
            // Calcula o novo valor baseado na moeda atual
            const newValue = isCurrentlyUSD ? (currentValue * exchangeRate) : (currentValue / exchangeRate);
            const newCurrency = isCurrentlyUSD ? 'BRL' : 'USD';
            
            const formData = new FormData();
            formData.append('action', 'update_currency');
            formData.append('video_id', videoId);
            formData.append('currency', newCurrency);
            formData.append('price', newValue.toFixed(2));
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    await loadVideos(currentMonth, currentYear);
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        async function togglePaymentStatus(button, videoId) {
            const formData = new FormData();
            formData.append('action', 'toggle_payment');
            formData.append('video_id', videoId);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    button.classList.toggle('bg-[#FF696C]');
                    button.classList.toggle('bg-[#9FFEBB]');
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Fechar modal ao clicar fora
        document.getElementById('notesModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });

        function closeModal() {
            document.getElementById('notesModal').classList.remove('active');
        }

        // Chamar checkAuth quando a página carregar
        document.addEventListener('DOMContentLoaded', async function() {
            await checkAuth();
        });

        function toggleTotalView() {
            const button = document.getElementById('totalToggle');
            const currentMonth = document.getElementById('monthSelect').value;
            const currentYear = document.getElementById('yearSelect').value;

            switch(totalViewState) {
                case 'all':
                    totalViewState = 'paid';
                    button.classList.remove('bg-[#B974ED]');
                    button.classList.add('bg-[#9FFEBB]');
                    break;
                case 'paid':
                    totalViewState = 'unpaid';
                    button.classList.remove('bg-[#9FFEBB]');
                    button.classList.add('bg-[#FF696C]');
                    break;
                case 'unpaid':
                    totalViewState = 'all';
                    button.classList.remove('bg-[#FF696C]');
                    button.classList.add('bg-[#B974ED]');
                    break;
            }

            loadVideos(currentMonth, currentYear);
        }

        document.getElementById('exportExcelButton').addEventListener('click', async function() {
            const formData = new FormData();
            formData.append('month', document.getElementById('monthSelect').value);
            formData.append('year', document.getElementById('yearSelect').value);
            formData.append('tagFilters', JSON.stringify(Array.from(activeTagFilters)));
            
            // Criar um form temporário para submeter
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export.php';
            
            // Adicionar os dados do FormData ao form
            for (let pair of formData.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = pair[0];
                input.value = pair[1];
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });

        async function deleteVideo() {
            const videoId = document.getElementById('currentVideoId').value;
            const currentMonth = document.getElementById('monthSelect').value;
            const currentYear = document.getElementById('yearSelect').value;
            
            if (!confirm('Tem certeza que deseja excluir este vídeo?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_video');
                formData.append('video_id', videoId);

                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    // Fechar o modal
                    document.getElementById('notesModal').classList.remove('active');
                    
                    // Recarregar os vídeos do mês atual
                    await loadVideos(currentMonth, currentYear);
                } else {
                    throw new Error(data.message || 'Erro ao excluir vídeo');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir vídeo. Por favor, tente novamente.');
            }
        }

        async function showNotes(videoId, notes, name, price, currency, videoDay = null) {
            document.getElementById('currentVideoId').value = videoId;
            document.getElementById('videoNotes').value = notes || '';
            document.getElementById('modalVideoName').value = name || '';
            
            // Configurar os valores em real e dólar
            if (currency === 'BRL') {
                document.getElementById('modalBrlPrice').value = price.toFixed(2);
                document.getElementById('modalUsdPrice').value = (price / exchangeRate).toFixed(2);
            } else {
                document.getElementById('modalUsdPrice').value = price.toFixed(2);
                document.getElementById('modalBrlPrice').value = (price * exchangeRate).toFixed(2);
            }
            
            // Configurar o dia e mostrar mês/ano selecionado
            document.getElementById('modalVideoDay').value = videoDay || '';
            const selectedMonth = document.getElementById('monthSelect').value;
            const selectedYear = document.getElementById('yearSelect').value;
            document.getElementById('modalVideoMonthYear').textContent = 
                `de ${months[parseInt(selectedMonth) - 1]} de ${selectedYear}`;
            
            // Carregar tags do vídeo
            await loadVideoTags();
            
            // Mostrar o modal
            document.getElementById('notesModal').classList.add('active');
        }

        function updateUsdFromBrl() {
            const brlPrice = parseFloat(document.getElementById('modalBrlPrice').value) || 0;
            document.getElementById('modalUsdPrice').value = (brlPrice / exchangeRate).toFixed(2);
        }

        function updateBrlFromUsd() {
            const usdPrice = parseFloat(document.getElementById('modalUsdPrice').value) || 0;
            document.getElementById('modalBrlPrice').value = (usdPrice * exchangeRate).toFixed(2);
        }

        async function switchMonth(direction) {
            if (isLoadingVideos) return;
            
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');
            let currentMonth = parseInt(monthSelect.value);
            let currentYear = parseInt(yearSelect.value);
            
            if (direction === 'next') {
                if (currentMonth === 12) {
                    currentMonth = 1;
                    currentYear++;
                } else {
                    currentMonth++;
                }
            } else {
                if (currentMonth === 1) {
                    currentMonth = 12;
                    currentYear--;
                } else {
                    currentMonth--;
                }
            }
            
            monthSelect.value = currentMonth;
            yearSelect.value = currentYear;
            
            updateMonthTitle(currentMonth, currentYear);
            await loadVideos(currentMonth, currentYear);
        }

        function initDragAndDrop() {
            const container = document.getElementById('videosContainer');
            const items = container.querySelectorAll('[draggable="true"]');

            items.forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragend', handleDragEnd);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
            });
        }

        let draggedItem = null;

        function handleDragStart(e) {
            draggedItem = this;
            this.style.opacity = '0.4';
            
            // Adiciona linhas de separação entre os itens
            const items = document.getElementById('videosContainer').children;
            Array.from(items).forEach((item, index) => {
                if (item !== draggedItem) {
                    const line = document.createElement('div');
                    line.className = 'drag-line';
                    item.parentNode.insertBefore(line, item);
                    
                    // Mostra a linha com um pequeno delay
                    setTimeout(() => line.style.opacity = '1', 50);
                }
            });
            
            // Adiciona uma linha final
            const lastLine = document.createElement('div');
            lastLine.className = 'drag-line';
            draggedItem.parentNode.appendChild(lastLine);
            setTimeout(() => lastLine.style.opacity = '1', 50);
        }

        function handleDragEnd(e) {
            this.style.opacity = '1';
            draggedItem = null;
            
            // Remove todas as linhas de separação
            const lines = document.getElementsByClassName('drag-line');
            while (lines.length > 0) {
                lines[0].remove();
            }

            // Atualiza a ordem após o drop
            updateOrder();
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            // Destaca a linha mais próxima
            const lines = document.getElementsByClassName('drag-line');
            Array.from(lines).forEach(line => {
                const rect = line.getBoundingClientRect();
                const distance = Math.abs(e.clientY - (rect.top + rect.height/2));
                line.style.opacity = distance < 20 ? '1' : '0.3';
            });
        }

        async function handleDrop(e) {
            e.preventDefault();
            if (this === draggedItem) return;

            const container = document.getElementById('videosContainer');
            const items = [...container.children];
            const fromIndex = items.indexOf(draggedItem);
            const toIndex = items.indexOf(this);

            if (fromIndex < toIndex) {
                this.parentNode.insertBefore(draggedItem, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedItem, this);
            }

            await updateOrder();
        }

        async function updateOrder() {
            const items = document.getElementById('videosContainer').children;
            const orders = Array.from(items).map((item, index) => ({
                id: item.dataset.videoId,
                order: index
            }));

            const formData = new FormData();
            formData.append('action', 'update_order');
            formData.append('orders', JSON.stringify(orders));

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    console.error('Erro ao atualizar ordem');
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        function closeTagsModal() {
            // Ao fechar sem salvar, descarta as alterações temporárias
            tempSelectedTags = new Set(selectedTags);
            document.getElementById('tagsModal').classList.remove('active');
        }

        async function loadTags() {
            const formData = new FormData();
            formData.append('action', 'get_tags');
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    allTags = data.tags;
                    updateFilterTags(data.tags);
                    updateTagsList(data.tags);
                    updateSelectedTagsDisplay();
                }
            } catch (error) {
                console.error('Erro ao carregar tags:', error);
            }
        }

        function updateTagsList(tags) {
            const container = document.getElementById('tagsList');
            let html = '';
            
            tags.forEach(tag => {
                // Usa tempSelectedTags para mostrar o estado atual da seleção
                const isSelected = tempSelectedTags.has(parseInt(tag.id));
                html += `
                    <div class="flex items-center justify-between bg-[#202020] p-2 rounded-md">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" ${isSelected ? 'checked' : ''} 
                                   onchange="toggleTag(${tag.id})" class="rounded">
                            <span class="px-2 py-1 rounded" style="background-color: ${tag.color}">
                                ${tag.name}
                            </span>
                        </div>
                        <button onclick="deleteTag(${tag.id})" class="text-[#FF696C] hover:text-[#FF696C]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function toggleTag(tagId) {
            tagId = parseInt(tagId); // Garante que o ID seja um número
            if (tempSelectedTags.has(tagId)) {
                tempSelectedTags.delete(tagId);
            } else {
                tempSelectedTags.add(tagId);
            }
            updateTagsList(allTags);
        }

        function saveTagSelections() {
            selectedTags = new Set(tempSelectedTags);
            updateSelectedTagsDisplay();
            closeTagsModal();
        }

        async function deleteTag(tagId) {
            if (!confirm('Tem certeza que deseja excluir esta tag?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_tag');
            formData.append('tag_id', tagId);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    selectedTags.delete(tagId);
                    tempSelectedTags.delete(tagId);
                    await loadTags();
                    updateSelectedTagsDisplay();
                }
            } catch (error) {
                console.error('Erro ao excluir tag:', error);
            }
        }

        function openTagsModal() {
            // Copia as tags atualmente selecionadas para a seleção temporária
            tempSelectedTags = new Set(selectedTags);
            document.getElementById('tagsModal').classList.add('active');
            loadTags();
        }

        async function openVideoTagsModal() {
            const videoId = document.getElementById('currentVideoId').value;
            const videoTagsSelection = document.getElementById('videoTagsSelection');
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_video_tags');
                formData.append('video_id', videoId);
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    selectedVideoTags = new Set(data.selectedTags.map(tag => parseInt(tag.id)));
                    tempVideoTags = new Set(selectedVideoTags);
                    
                    videoTagsSelection.innerHTML = '';
                    data.allTags.forEach(tag => {
                        const isSelected = tempVideoTags.has(parseInt(tag.id));
                        const tagDiv = document.createElement('div');
                        tagDiv.className = 'flex items-center justify-between bg-[#202020] p-2 rounded-md';
                        tagDiv.innerHTML = `
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="vtag_${tag.id}" 
                                       ${isSelected ? 'checked' : ''} 
                                       onchange="toggleVideoTag(${tag.id})" 
                                       class="rounded">
                                <label for="vtag_${tag.id}" class="flex-1 cursor-pointer">
                                    <span class="px-2 py-1 rounded text-sm" 
                                          style="background-color: ${tag.color}">
                                        ${tag.name}
                                    </span>
                                </label>
                            </div>
                        `;
                        videoTagsSelection.appendChild(tagDiv);
                    });
                }
                
                document.getElementById('videoTagsModal').classList.add('active');
            } catch (error) {
                console.error('Erro ao carregar tags:', error);
            }
        }

        function updateFilterTags(tags) {
            const filterTagsContainer = document.getElementById('filterTags');
            const filterTagsHtml = tags.map(tag => {
                const color = tag.color;
                const isActive = activeTagFilters.has(parseInt(tag.id));
                
                return `
                    <div class="cursor-pointer px-3 py-1 rounded-md text-sm font-medium transition-all"
                         style="background-color: ${color}; 
                                color: #202020; 
                                ${!isActive ? 'filter: brightness(85%);' : ''}
                                ${isActive ? 'border: 2px solid #FFFFFF; box-shadow: 0 0 5px rgba(255,255,255,0.5);' : ''}"
                         onclick="toggleTagFilter(${tag.id})">
                        ${tag.name}
                    </div>
                `;
            }).join('');
            
            filterTagsContainer.innerHTML = filterTagsHtml;
        }

        function toggleTagFilter(tagId) {
            tagId = parseInt(tagId);
            if (activeTagFilters.has(tagId)) {
                activeTagFilters.delete(tagId);
            } else {
                activeTagFilters.add(tagId);
            }
            updateFilterTags(allTags);
            filterVideos();
        }

        function filterVideos() {
            const videos = document.querySelectorAll('#videosContainer > div');
            let total = 0;
            let totalUsd = 0;

            videos.forEach(video => {
                const isPaid = video.dataset.paid === '1';
                const videoTags = new Set(JSON.parse(video.dataset.tags || '[]').map(t => parseInt(t)));
                
                // Verifica se o vídeo deve ser mostrado baseado nas tags selecionadas
                let shouldShowByTags = true;
                if (activeTagFilters.size > 0) {
                    shouldShowByTags = Array.from(activeTagFilters).every(tagId => 
                        videoTags.has(tagId)
                    );
                }

                // Verifica o estado do filtro de pagamento
                let shouldShowByPayment = true;
                if (totalViewState === 'paid') {
                    shouldShowByPayment = isPaid;
                } else if (totalViewState === 'unpaid') {
                    shouldShowByPayment = !isPaid;
                }

                // Combina os dois filtros
                const shouldShow = shouldShowByTags && shouldShowByPayment;
                
                video.style.display = shouldShow ? 'flex' : 'none';

                // Atualiza o total apenas para vídeos visíveis
                if (shouldShow) {
                    const price = parseFloat(video.dataset.price);
                    const currency = video.dataset.currency;
                    
                    if (currency === 'BRL') {
                        total += price;
                        totalUsd += price / exchangeRate;
                    } else {
                        totalUsd += price;
                        total += price * exchangeRate;
                    }
                }
            });

            // Atualiza os totais
            document.getElementById('totalValue').textContent = `R$ ${total.toFixed(2)}`;
        }

        async function createTag() {
            const tagName = document.getElementById('newTagName').value.trim();
            const tagColor = document.getElementById('newTagColor').value;
            
            if (!tagName) {
                alert('Por favor, insira um nome para a tag');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'createTag');
            formData.append('name', tagName);
            formData.append('color', tagColor);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    // Limpar campos
                    document.getElementById('newTagName').value = '';
                    document.getElementById('newTagColor').value = '#000000';
                    
                    // Atualizar lista de tags
                    await loadTags();
                } else {
                    alert(data.message || 'Erro ao criar tag');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao criar tag. Por favor, tente novamente.');
            }
        }

        // Verifica a preferência do usuário ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const preferredUI = localStorage.getItem('uiVersion');
            const currentFile = window.location.pathname.split('/').pop();
            
            if (preferredUI === 'new' && currentFile === 'originalUi.php') {
                window.location.href = 'index.php';
            } else if (preferredUI === 'old' && currentFile === 'index.php') {
                window.location.href = 'originalUi.php';
            }
        });

        function updateSelectedTagsDisplay() {
            // Remove o container existente se não houver tags selecionadas
            const existingContainer = document.getElementById('selectedTagsDisplay');
            if (existingContainer) {
                existingContainer.remove();
            }

            // Se não houver tags selecionadas, não cria o container
            if (selectedTags.size === 0) {
                return;
            }

            // Cria o container principal
            const displayContainer = document.createElement('div');
            displayContainer.id = 'selectedTagsDisplay';
            displayContainer.className = 'mt-2'; // Adiciona margem superior

            // Cria o container para as tags
            const container = document.createElement('div');
            container.className = 'flex flex-wrap gap-2';
            
            // Adiciona cada tag selecionada
            selectedTags.forEach(tagId => {
                const tag = allTags.find(t => t.id === parseInt(tagId));
                if (tag) {
                    const tagElement = document.createElement('span');
                    tagElement.className = 'px-2 py-1 rounded text-sm text-white';
                    tagElement.style.backgroundColor = tag.color;
                    tagElement.textContent = tag.name;
                    container.appendChild(tagElement);
                }
            });
            
            // Adiciona o container de tags ao container principal
            displayContainer.appendChild(container);
            
            // Adiciona o container principal após o botão de tags
            const parentElement = document.getElementById('openTagsButton').parentNode;
            parentElement.appendChild(displayContainer);
        }

        async function loadVideoTags() {
            const videoId = document.getElementById('currentVideoId').value;
            const formData = new FormData();
            formData.append('action', 'get_video_tags');
            formData.append('video_id', videoId);
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    selectedVideoTags = new Set(data.selectedTags.map(tag => parseInt(tag.id)));
                    tempVideoTags = new Set(selectedVideoTags);
                    
                    // Atualiza a exibição das tags no modal
                    const container = document.getElementById('videoTagsSelection');
                    let html = '';
                    
                    data.allTags.forEach(tag => {
                        const isSelected = tempVideoTags.has(parseInt(tag.id));
                        html += `
                            <div class="flex items-center justify-between bg-[#202020] p-2 rounded-md">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="vtag_${tag.id}" 
                                           ${isSelected ? 'checked' : ''} 
                                           onchange="toggleVideoTag(${tag.id})" 
                                           class="rounded">
                                    <label for="vtag_${tag.id}" class="flex-1 cursor-pointer">
                                        <span class="px-2 py-1 rounded text-sm" 
                                              style="background-color: ${tag.color}">
                                            ${tag.name}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                    updateVideoTagsList(data.selectedTags);
                }
            } catch (error) {
                console.error('Erro ao carregar tags do vídeo:', error);
            }
        }

        function toggleVideoTag(tagId) {
            tagId = parseInt(tagId);
            if (tempVideoTags.has(tagId)) {
                tempVideoTags.delete(tagId);
            } else {
                tempVideoTags.add(tagId);
            }
        }

        async function saveVideoTags() {
            const videoId = document.getElementById('currentVideoId').value;
            const formData = new FormData();
            formData.append('action', 'save_video_tags');
            formData.append('video_id', videoId);
            formData.append('tags', JSON.stringify(Array.from(tempVideoTags)));
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    selectedVideoTags = new Set(tempVideoTags);
                    closeVideoTagsModal();
                    await loadVideoTags();
                    const month = document.getElementById('monthSelect').value;
                    const year = document.getElementById('yearSelect').value;
                    await loadVideos(month, year);
                }
            } catch (error) {
                console.error('Erro ao salvar tags do vídeo:', error);
            }
        }

        function updateVideoTagsList(tags) {
            const container = document.getElementById('videoTagsList');
            if (!container) return;
            
            container.innerHTML = '';
            tags.forEach(tag => {
                const tagElement = document.createElement('div');
                tagElement.className = 'px-2 py-1 rounded text-sm';
                tagElement.style.backgroundColor = tag.color;
                tagElement.textContent = tag.name;
                container.appendChild(tagElement);
            });
        }

        function closeVideoTagsModal() {
            tempVideoTags = new Set(selectedVideoTags);
            document.getElementById('videoTagsModal').classList.remove('active');
        }
    </script>
</body>
</html>