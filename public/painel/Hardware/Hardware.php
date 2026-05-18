<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Hackers_Brasil/init.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardwares , Componentes do Computador</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/header-global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Favicon básico -->
    <link rel="shortcut icon" href="../../images/Favicon4.png" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
</head>

<body>

   <header>
        <div class="container header-content">
			
			<div class="top-bar">
                <span><strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong></span>
                <a href="/Hackers_Brasil_New/codigos_php/logout.php" onclick="return confirm('Tem certeza que deseja sair?')"> Sair</a>
            </div>
            
            <div class="logo">
                <i class="fas fa-code"></i>
                <span>Hackers Brasil</span>
            </div>
            <nav>
                <ul>
                    <li><a href="sobre.html" target="_blank">Sobre</a></li>
                    <li><a href="ferramentas.html" target="_blank">Ferramentas</a></li>
                    <li><a href="#resources">Recursos</a></li>
                </ul>
            </nav>
                       
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

  <div class="container-principal">
	  <h1>Hardwares , Componentes do Computador</h1>
   
    <section class="introducao">
        <p>O hardware representa a parte física e tangível de um computador, compreendendo todos os componentes eletrônicos, circuitos e dispositivos que trabalham em conjunto para processar informações e executar tarefas.</p>
        <br>
        <p>Conhecer profundamente cada componente - suas funções específicas, características técnicas, formas de interconexão e compatibilidade - é fundamental não apenas para montagem e manutenção de sistemas, mas também para otimização de desempenho, solução de problemas e até mesmo para áreas especializadas como cibersegurança, onde o entendimento da infraestrutura física é crucial para implementar medidas de proteção adequadas.</p>
    </section>

    <section class="componentes-principais">
      <h2>Componentes Principais</h2>

      <p>Os componentes principais são as peças fundamentais para o funcionamento básico de um computador. Estes incluem a placa-mãe, processador (CPU), memória RAM, armazenamento (HD/SSD), placa de vídeo (GPU) e fonte de alimentação. Sem qualquer um desses componentes, o computador não conseguiria inicializar ou executar suas funções básicas.</p>
      <br>
      <h3>Placa-mãe (Motherboard)</h3>
        <p>A placa-mãe é o componente central que interconecta todos os dispositivos do computador. Os principais fatores de forma (tamanhos) disponíveis no mercado incluem:</p>
        <ul class="commands">
		   <li><code><strong>ATX (305mm x 244mm)</strong></code> - o mais comum para desktops completos;</li>
		   <li><code><strong>Micro-ATX (244mm x 244mm)</strong></code> - menor e mais econômico;</li>
		   <li><code><strong>Mini-ITX (170mm x 170mm)</strong></code> - utilizado em computadores compactos,<li>
		   <li><code><strong>E-ATX  305 x 330 mm</strong></code> - mais voltado para Workstations, servidores.</li> 
	    </li>
	    <p>A placa-mãe integra o chipset (que gerencia a comunicação entre a CPU, RAM, periféricos e barramentos), os slots de expansão PCIe (para placas de vídeo, rede, armazenamento), soquetes para memória RAM DIMM (DDR4 ou DDR5), conectores SATA para HDs e SSDs, além de portas USB, Ethernet, áudio e vídeo (HDMI, DisplayPort, VGA) no painel traseiro.</p> 
        <br>
        <p><strong>📌 Obs:</strong> <strong>ATX</strong> (Advanced Technology Extended) é um padrão criado pela Intel em 1995, não uma marca. Assim como USB ou HDMI, é um formato que fabricantes seguem para garantir compatibilidade entre componentes.</p>
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/Placa_Mae.jpg" 
         alt="Placa Mãe"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">Placa Mãe</p>
        <br>
        <p>A placa-mãe é o coração do seu computador e escolher um bom modelo garante longevidade à sua máquina. Se você pretende usar o PC por vários anos sem fazer upgrades frequentes, invista em uma placa com chipset de qualidade, suporte para DDR5 (ou a geração mais recente disponível), slots PCIe 4.0 ou 5.0 e bons dissipadores de calor. Placas-mãe de entrada podem economizar agora, mas costumam limitar futuros upgrades de processador, memória e armazenamento.</p>
        <br>
        <p><strong>Marcas confiáveis no mercado:</strong> As fabricantes mais consolidadas e recomendadas incluem <strong>ASUS</strong> (conhecida pela durabilidade e recursos avançados), <strong>MSI</strong> (excelente custo-benefício e construção robusta), <strong>Gigabyte</strong> (boa variedade de modelos e inovação) e <strong>ASRock</strong> (opções sólidas para diferentes orçamentos). Independente da marca, sempre verifique a procedência e a garantia do produto.</p>
        <br> 
      <h3>CPU (Processador)</h3>
        <p>A CPU (Central Processing Unit) é responsável por interpretar e executar instruções dos programas. Os principais fabricantes do mercado são <strong>Intel</strong> e <strong>AMD</strong>. Para escolher um processador que dure por anos, considere: maior número de núcleos (cores) e threads (6 ou 8 núcleos é o ideal atual), cache elevado (L2 e L3), litografia mais avançada (7nm, 5nm - quanto menor, mais eficiente) e suporte a tecnologias modernas como DDR5 e PCIe 4.0/5.0. Modelos de entrada (como Intel Celeron ou AMD Athlon) são para tarefas básicas; já as linhas Intel Core (i5/i7/i9) e AMD Ryzen (5/7/9) oferecem desempenho para jogos, programação e multitarefa pesada.</p> 
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/CPU.jpg" 
         alt="CPU"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">CPU Intel</p>
        <br>
        <p>A CPU é o cérebro do computador. Ela realiza cálculos, executa instruções e comanda as operações do sistema.</p>
        <br>
      <h3>RAM (Memória de Acesso Aleatório)</h3>
        <p>A RAM (Random Access Memory) é a memória volátil que armazena temporariamente os dados que o processador está utilizando no momento. Os principais tipos disponíveis atualmente são <strong>DDR4</strong> (ainda comum) e <strong>DDR5</strong> (mais nova e rápida). Para um computador que rode bem programas, navegação e jogos por alguns anos, recomenda-se no mínimo <strong>16GB</strong> (8GB já está ficando limitado). Frequências mais altas (3200MHz para DDR4 ou 5600MHz+ para DDR5) entregam melhor desempenho. Marcas confiáveis incluem <strong>Kingston</strong> (especialmente linha Fury), <strong>Corsair</strong> (linha Vengeance), <strong>Crucial</strong>, <strong>ADATA</strong> e <strong>G.Skill</strong>. Sempre verifique se a placa-mãe é compatível com o tipo e a frequência da RAM escolhida.</p>
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/Mem_Ram.jpg" 
         alt="Memória Ram"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">Memória Ram</p>
        <br>
        <p>A RAM armazena dados temporariamente enquanto programas estão em execução. Quanto mais RAM, mais tarefas o computador consegue realizar ao mesmo tempo.</p>
        <br>
      <h3>HD/SSD (Armazenamento)</h3>
      <p>O armazenamento guarda seus dados de forma permanente (sistema operacional, programas, arquivos). A principal diferença entre HD e SSD está na tecnologia: <strong>HD (Hard Disk)</strong> utiliza discos magnéticos giratórios e cabeças de leitura/gravação, sendo mais lento e suscetível a impactos; <strong>SSD (Solid State Drive)</strong> usa memória flash NAND, sem partes móveis, entregando velocidades muito superiores (até 500MB/s em SATA e 3500MB/s+ em NVMe). Para o sistema operacional e programas, <strong>sempre escolha um SSD</strong> — o ganho de desempenho é absurdo (boot em segundos). HDs ainda valem a pena apenas para armazenar muitos arquivos (backups, fotos, vídeos) com baixo custo por GB. Capacidades recomendadas: 240GB/480GB SSD para sistema + 1TB/2TB HD para dados, ou 1TB SSD NVMe se o orçamento permitir. Marcas confiáveis: <strong>Kingston</strong>, <strong>Western Digital (WD)</strong>, <strong>Samsung</strong> (topo de linha), <strong>Crucial</strong>, <strong>SanDisk</strong> e <strong>Seagate</strong> (para HDs).</p>
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/HD_SSD.webp" 
         alt="Hd_SSD"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">HD e SSD</p>
        <br>
        <p>HDs (discos rígidos) e SSDs (unidades de estado sólido) armazenam dados permanentemente. SSDs são mais rápidos e duráveis que HDs.</p>
        <br>
      <h3>Placa de Vídeo (GPU)</h3>
        <p>A GPU (Graphics Processing Unit) é responsável por processar e renderizar imagens, vídeos e animações, aliviando a carga da CPU. Os dois principais fabricantes de chipsets são <strong>NVIDIA</strong> (linhas GeForce RTX, para jogos e IA, e Quadro, para trabalho profissional) e <strong>AMD</strong> (linhas Radeon RX, excelente custo-benefício, e Radeon Pro). Um terceiro player crescente é a <strong>Intel</strong>, com suas placas Arc (ainda em evolução, mas promissoras). Para escolher uma GPU que dure por anos, considere: <strong>VRAM (memória de vídeo)</strong> — mínimo 8GB para jogos atuais, 12GB+ para futuro; <strong>largura de barramento</strong> (192-bit ou mais); <strong>suporte a tecnologias</strong> como DLSS (NVIDIA), FSR (AMD) e Ray Tracing. Marcas confiáveis que montam placas com esses chipsets incluem <strong>EVGA</strong>, <strong>ASUS</strong> (linha TUF, ROG), <strong>MSI</strong>, <strong>Gigabyte</strong> (linha Aorus), <strong>Zotac</strong> e <strong>Sapphire</strong> (especialista em AMD). Para uso profissional (IA, renderização 3D), placas com muita VRAM (16GB+, como NVIDIA RTX 4060 Ti 16GB ou AMD Radeon RX 7700 XT) são mais indicadas do que GPUs de entrada com 4GB.</p> 
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/Placa_Video.jpg" 
         alt="Placa de Vídeo"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">Placa de Vídeo</p>
        <br>
        <p>A placa de vídeo é responsável por processar e renderizar gráficos, aliviando a carga da CPU. Fundamental para jogos, edição de vídeo e aplicações gráficas intensivas.</p>
        <br>
      <h3>Fonte de Alimentação (PSU)</h3>
        <p>A fonte (PSU - Power Supply Unit) converte a corrente alternada (AC) da tomada em corrente contínua (DC) nos volts adequados para cada componente (+3.3V, +5V, +12V). Para escolher uma fonte de qualidade que dure anos e proteja seu hardware, observe: <strong>certificação 80 Plus</strong> (Bronze, Silver, Gold, Platinum, Titanium) — garante eficiência energética mínima de 80%; <strong>potência real (watts)</strong> — calcule o consumo total do sistema (CPU + GPU + folga) e adicione 30% de margem (ex: um PC gamer médio precisa de 550W a 650W); <strong>proteções</strong> — OVP (sobretensão), UVP (subtensão), OCP (sobrecorrente), SCP (curto-circuito), OTP (superaquecimento). Marcas confiáveis e modelos reconhecidos incluem <strong>Corsair</strong> (linhas RMx, CX-M), <strong>XPG</strong> (Core Reactor), <strong>Cooler Master</strong> (MWE Gold), <strong>SeaSonic</strong> (referência em qualidade), <strong>EVGA</strong> (SuperNOVA) e <strong>Thermaltake</strong> (Toughpower). Fuja de fontes genéricas sem certificação ou marcas desconhecidas — elas colocam todo o computador em risco.</p>
        <br>
        <img 
         src="https://kdfdbuqxwdozsdmchyxj.supabase.co/storage/v1/object/public/images/Hardware/Fonte_Alimentaca.webp" 
         alt="Placa de Vídeo"
         style="
                max-width: 60%;
                height: auto;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                display: block;
                margin: 0 auto;
               "
        >   
        <p class="legenda-imagem" style="text-align: center;">Placa de Vídeo</p>
        <br>
        <p>A fonte converte a energia elétrica da tomada para os níveis adequados aos componentes do computador. Uma fonte de qualidade é essencial para estabilidade e durabilidade do sistema.</p>
    </section>

    <section class="componentes-secundarios">
      <h2>Componentes Secundários</h2>
       
        <p>Os componentes secundários complementam o sistema, fornecendo funcionalidades adicionais, interfaces de usuário e melhorias de desempenho. Esta categoria inclui coolers para refrigeração, gabinete para proteção e organização, monitor para exibição visual, e periféricos como teclado e mouse para interação com o sistema.</p> 

      <h3>Cooler/Ventilador</h3>
        <img src="images/cooler.jpg" alt="Imagem de um Cooler" class="imagem-hardware">
        <p>
          Os coolers são responsáveis por manter a temperatura adequada dos componentes, evitando superaquecimento. Podem ser instalados no processador, gabinete e placas de vídeo.
        </p>
    
      <h3>Gabinete</h3>
        <img src="images/gabinete.jpg" alt="Imagem de um Gabinete" class="imagem-hardware">
        <p>
          O gabinete abriga e protege todos os componentes internos do computador. Modelos com boa ventilação e organização de cabos são ideais para manter o sistema refrigerado.
        </p>
      
      <h3>Monitor</h3>
        <img src="images/monitor.jpg" alt="Imagem de um Monitor" class="imagem-hardware">
        <p>
          O monitor é o dispositivo de saída que exibe informações visuais. Características como resolução, taxa de atualização e tipo de painel influenciam na experiência do usuário.
        </p>
      
      <h3>Teclado</h3>
        <img src="images/teclado.jpg" alt="Imagem de um Teclado" class="imagem-hardware">
        <p>
          O teclado é o principal dispositivo de entrada para inserção de texto e comandos. Existem diversos tipos, desde membranas até mecânicos, com diferentes características.
        </p>
    </section>

    <section class="alias">
      <h2>Comandos para Verificar Hardware</h2>
      <p>Conhecer comandos para verificar informações do hardware é essencial para diagnóstico e manutenção.</p>
      <ul class="commands">
        <li><pre><code><strong>lshw -short</strong></code> - Exibe informações detalhadas do hardware no Linux</pre></li>
        <li><pre><code><strong>systeminfo</strong></code> - Mostra informações do sistema no Windows</pre></li>
        <li><pre><code><strong>lscpu</strong></code> - Exibe informações sobre a CPU no Linux</pre></li>
        <li><pre><code><strong>free -h</strong></code> - Mostra uso de memória RAM no Linux</pre></li>
      </ul>
    </section>

    <section class="dicas-montagem">
      <h2>Dicas para Montar sua Máquina</h2>
      <p>
        Ao montar um computador, é importante que os componentes sejam compatíveis entre si. Aqui estão algumas sugestões:
      </p>

      <ul>
        <li><p>Verifique a compatibilidade entre CPU e Placa-mãe (<a href="#" id="soqueteLink">soquete</a> e <a href="#" id="chipsetLink">chipset</a>).</p></li>
        <li><p>Verifique a compatibilidade entre a memória <a href="#" id="ramLink">RAM</a> e a placa-mãe (tipo e frequência suportados).</p></li>
        <li><p>Escolha <a href="#" id="nvmeLink">SSDs NVMe</a> ou <a href="#" id="sataLink">SSDs SATA</a> para melhor desempenho.</p></li>
        <li><p>Tenha uma boa fonte de alimentação (certificação 80 Plus).</p></li>
        <li><p>Use gabinetes com boa ventilação.</p></li>
        <li><p>Considere o consumo energético total ao escolher a fonte.</p></li>
        <li><p>Planeje o fluxo de ar dentro do gabinete para melhor refrigeração.</p></li>
      </ul>
    </section>

    <section class="tables">
      <h2>Comparativo de Tecnologias</h2>
      <table>
        <thead>
          <tr>
            <th>Componente</th>
            <th>Tecnologia</th>
            <th>Vantagens</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Armazenamento</td>
            <td>SSD NVMe</td>
            <td>Velocidade superior, menor latência</td>
          </tr>
          <tr>
            <td>Armazenamento</td>
            <td>SSD SATA</td>
            <td>Bom custo-benefício, compatibilidade ampla</td>
          </tr>
          <tr>
            <td>Armazenamento</td>
            <td>HDD</td>
            <td>Maior capacidade por menor custo</td>
          </tr>
          <tr>
            <td>Memória</td>
            <td>DDR5</td>
            <td>Maior velocidade e eficiência energética</td>
          </tr>
          <tr>
            <td>Memória</td>
            <td>DDR4</td>
            <td>Estabilidade, ampla compatibilidade</td>
          </tr>
        </tbody>
      </table>
    </section>
  
    <section class="onde-comprar">
      <h2>Onde Comprar Componentes</h2>
      <p>
        Confira algumas lojas confiáveis para adquirir hardware e montar seu computador:
      </p>
      <ul>
        <li><p><a href="https://www.pichau.com.br" target="_blank">Pichau Informática</a></p></li>
        <li><p><a href="https://www.kabum.com.br" target="_blank">Kabum!</a></p></li>
        <li><p><a href="https://www.terabyteshop.com.br" target="_blank">Terabyte Shop</a></p></li>
      </ul>
      <p><em>Nota:</em> Compare preços e verifique avaliações antes de comprar.</p>
    </section>
  </div>

  <!-- Modais -->
  <div id="modalSoquete" class="modal-global">
    <div class="modal-content">
      <span class="close" id="closeSoquete">&times;</span>
      <h2>Soquete</h2>
      <p>O soquete (ou socket) é o encaixe físico onde o processador é instalado na placa-mãe.</p>
      <img src="images/soquete.jpg" alt="soquete" width="250" height="175" class="image-soquete"> 
      <p>Exemplo:</p>
      <ul>
        <li>Intel LGA 1151, LGA 1200, LGA 1700.</li>
        <li>AMD AM4, AM5, TR4.</li>
      </ul>
      <p>A CPU só encaixa se for do mesmo soquete que a placa-mãe.</p>
    </div>
  </div>

  <div id="modalChipset" class="modal-global">
    <div class="modal-content">
      <span class="close" id="closeChipset">&times;</span>
      <h2>Chipset</h2>
      <p>O chipset controla a comunicação entre CPU, RAM, placa de vídeo e demais dispositivos.</p>
      <p>Cada geração de processadores tem chipsets compatíveis.</p>
      <img src="images/Chipset.jpg" alt="chipset" width="250" height="175" class="image-chipset">
      <p>Exemplo:</p>
      <ul>
        <li>Para Intel 10ª geração (Comet Lake) → chipsets Z490, B460, H410.</li>
        <li>Para AMD Ryzen série 5000 → chipsets B550, X570.</li>
      </ul>
      <p>Mesmo que o soquete seja compatível, a placa-mãe pode precisar de<br>
        atualização de BIOS para suportar CPUs mais novas no mesmo soquete.</p>
    </div>
  </div>

  <div id="modalRAM" class="modal-global">
    <div class="modal-content">
      <span class="close" id="closeRAM">&times;</span>
      <h2>Memória RAM</h2>
      <p>A memória RAM (Random Access Memory) é responsável por armazenar temporariamente os dados utilizados pelo sistema e pelos programas enquanto estão em execução.</p>
      <img src="images/mem_ram.jpg" alt="ram" width="250" height="175" class="image-ram">
      <p>Antes de comprar, verifique:</p>
      <ul>
        <li><strong>Tipo:</strong> DDR3, DDR4 ou DDR5, conforme a placa-mãe suporta.</li>
        <li><strong>Frequência:</strong> A velocidade suportada pela placa-mãe (ex.: 2400 MHz, 3200 MHz).</li>
        <li><strong>Capacidade máxima:</strong> Limite de GB que a placa-mãe aceita e quantidade de slots disponíveis.</li>
      </ul>
      <p>Usar RAM compatível garante estabilidade e desempenho no seu computador.</p>
    </div>
  </div>

  <div id="modalNVMe" class="modal-global">
    <div class="modal-content">
      <span class="close" id="closeNVMe">&times;</span>
      <h2>SSDs NVMe</h2>
      <p>Os SSDs NVMe (Non-Volatile Memory Express) são unidades de armazenamento de alta velocidade que utilizam o barramento PCIe para transferências de dados muito mais rápidas do que os SSDs SATA tradicionais.</p>
      <img src="images/nvme.jpg" alt="nvme" width="250" height="175" class="image-nvme">
      <p>Principais vantagens:</p>
      <ul>
        <li>Velocidade de leitura e gravação muito superior aos SSDs SATA.</li>
        <li>Redução do tempo de boot e carregamento de aplicativos.</li>
        <li>Melhor desempenho em tarefas pesadas e em jogos.</li>
        <li>Menor latência no acesso a dados.</li>
      </ul>
      <p>Ao montar seu computador, opte por placas-mãe que possuem slot M.2 compatível com NVMe para utilizar todo o potencial destes SSDs.</p>
    </div>
  </div>

  <div id="modalSATA" class="modal-global">
    <div class="modal-content">
      <span class="close" id="closeSATA">&times;</span>
      <h2>SSDs SATA</h2>
      <p>Os SSDs SATA utilizam a interface SATA III, oferecendo velocidades muito superiores aos HDs convencionais.</p>
      <img src="images/ssd_sata.jpg" alt="ssd" width="250" height="175" class="image-ssd">
      <p>Principais vantagens:</p>
      <ul>
        <li>Melhoria significativa na velocidade de inicialização e carregamento de programas.</li>
        <li>Maior confiabilidade e menor risco de falhas físicas comparado a HDs mecânicos.</li>
        <li>Preço mais acessível que SSDs NVMe.</li>
      </ul>
      <p>São ideais para quem busca desempenho superior gastando menos, sendo compatíveis com a maioria das placas-mãe.</p>
    </div>
  </div>

 <footer>
    <p>&copy; 2025 - Portal Hackers Brasil | Todos os direitos reservados</p>
    <div class="redes-sociais">
      <a href="#" target="_blank"><i class="fab fa-youtube"></i></a>
      <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
    </div>
  </footer>

<script defer src="../../assets/js/header-global.js"></script>
<script defer src="../../assets/js/script.js"></script>
<script defer src="../../assets/js/search.js" defer></script>

</body>
</html>
