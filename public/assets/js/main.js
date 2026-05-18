document.addEventListener('DOMContentLoaded', function() {
    // Menu Mobile
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const nav = document.querySelector('nav ul');
    
    mobileMenuBtn.addEventListener('click', () => {
        nav.classList.toggle('active');
    });
    
    // Fechar menu ao clicar em um link (mobile)
    document.querySelectorAll('nav ul li a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                nav.classList.remove('active');
            }
        });
    });
    

    // Seleciona o botão pelo id
const loginBtn = document.getElementById('loginBtn');

// Adiciona um evento de clique
loginBtn.addEventListener('click', function(event) {
    event.preventDefault(); // impede o comportamento padrão do link
    // Redireciona para login.html
    window.location.href = 'login.php';
});

    // =======================
    // Carrossel 1 (Ferramentas)
    // =======================
    const carousel1 = document.querySelectorAll('.carousel-container')[0];
    const images1 = carousel1.querySelectorAll('.carousel img');
    const prevBtn1 = carousel1.querySelector('.prev');
    const nextBtn1 = carousel1.querySelector('.next');
    const indicatorsContainer1 = carousel1.querySelector('.indicators');
    
    let currentIndex1 = 0;
    
    // Criar indicadores dinamicamente
    images1.forEach((_, index) => {
        const dot = document.createElement('span');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide1(index));
        indicatorsContainer1.appendChild(dot);
    });
    
    const dots1 = carousel1.querySelectorAll('.indicators span');
    
    function showSlide1(index) {
        images1.forEach(img => img.classList.remove('active'));
        images1[index].classList.add('active');
        
        dots1.forEach(dot => dot.classList.remove('active'));
        dots1[index].classList.add('active');
        
        currentIndex1 = index;
    }
    
    function nextSlide1() {
        const newIndex = (currentIndex1 + 1) % images1.length;
        showSlide1(newIndex);
    }
    
    function prevSlide1() {
        const newIndex = (currentIndex1 - 1 + images1.length) % images1.length;
        showSlide1(newIndex);
    }
    
    function goToSlide1(index) {
        showSlide1(index);
    }
    
    nextBtn1.addEventListener('click', nextSlide1);
    prevBtn1.addEventListener('click', prevSlide1);
    
    // =======================
    // Carrossel 2 (Recursos)
    // =======================
    const carousel2 = document.querySelectorAll('.carousel-container')[1];
    const images2 = carousel2.querySelectorAll('.carousel img');
    const prevBtn2 = carousel2.querySelector('.prev');
    const nextBtn2 = carousel2.querySelector('.next');
    const indicatorsContainer2 = carousel2.querySelector('.indicators');
    
    let currentIndex2 = 0;
    
    // Criar indicadores dinamicamente
    images2.forEach((_, index) => {
        const dot = document.createElement('span');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide2(index));
        indicatorsContainer2.appendChild(dot);
    });
    
    const dots2 = carousel2.querySelectorAll('.indicators span');
    
    function showSlide2(index) {
        images2.forEach(img => img.classList.remove('active'));
        images2[index].classList.add('active');
        
        dots2.forEach(dot => dot.classList.remove('active'));
        dots2[index].classList.add('active');
        
        currentIndex2 = index;
    }
    
    function nextSlide2() {
        const newIndex = (currentIndex2 + 1) % images2.length;
        showSlide2(newIndex);
    }
    
    function prevSlide2() {
        const newIndex = (currentIndex2 - 1 + images2.length) % images2.length;
        showSlide2(newIndex);
    }
    
    function goToSlide2(index) {
        showSlide2(index);
    }
    
    nextBtn2.addEventListener('click', nextSlide2);
    prevBtn2.addEventListener('click', prevSlide2);
    
    // =======================
    // Cookie Consent
    // =======================
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptAllBtn = document.getElementById('acceptAll');
    const rejectAllBtn = document.getElementById('rejectAll');
    const preferencesBtn = document.getElementById('preferencesBtn');
    const cookiePreferences = document.getElementById('cookiePreferences');
    const savePreferencesBtn = document.getElementById('savePreferences');
    
    // Verificar se o usuário já fez uma escolha
    if (!localStorage.getItem('cookieConsent')) {
        setTimeout(() => {
            cookieConsent.classList.add('active');
        }, 1000);
    }
    
    // Aceitar todos os cookies
    acceptAllBtn.addEventListener('click', () => {
        localStorage.setItem('cookieConsent', 'all');
        cookieConsent.classList.remove('active');
    });
    
    // Rejeitar todos os cookies não essenciais
    rejectAllBtn.addEventListener('click', () => {
        localStorage.setItem('cookieConsent', 'necessary');
        cookieConsent.classList.remove('active');
    });
    
    // Mostrar/ocultar preferências
    preferencesBtn.addEventListener('click', () => {
        cookiePreferences.classList.toggle('active');
    });
    
    // Salvar preferências
    savePreferencesBtn.addEventListener('click', () => {
        const analyticsChecked = document.querySelector('input[name="analytics"]:checked');
        const marketingChecked = document.querySelector('input[name="marketing"]:checked');
        
        const preferences = {
            necessary: true,
            analytics: !!analyticsChecked,
            marketing: !!marketingChecked
        };
        
        localStorage.setItem('cookieConsent', JSON.stringify(preferences));
        cookieConsent.classList.remove('active');
    });
});

// Envia dados assim que a página carregar
sendClientData();
