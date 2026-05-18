// Abrir modais
//Soquete
document.getElementById("soqueteLink").addEventListener("click", function(e) {
  e.preventDefault();
  document.getElementById("modalSoquete").style.display = "block";
  document.body.style.overflow = "hidden"; // trava o fundo
});

//Chipset
    
document.getElementById("chipsetLink").addEventListener("click", function(e) {
  e.preventDefault();
  document.getElementById("modalChipset").style.display = "block";
  document.body.style.overflow = "hidden"; // trava o fundo
});

//RAM
document.getElementById("ramLink").addEventListener("click", function(event){
    event.preventDefault();
    document.getElementById("modalRAM").style.display = "block";
    document.body.style.overflow = "hidden"; // trava o fundo
});

// SSD NVMe
document.getElementById("nvmeLink").addEventListener("click", function(e) {
  e.preventDefault();
  document.getElementById("modalNVMe").style.display = "block";
  document.body.style.overflow = "hidden"; // trava o fundo
});

// SSD SATA
document.getElementById("sataLink").addEventListener("click", function(e) {
  e.preventDefault();
  document.getElementById("modalSATA").style.display = "block";
  document.body.style.overflow = "hidden"; // trava o fundo
});

// Fechar modais ao clicar no X
//Soquete
document.getElementById("closeSoquete").addEventListener("click", function() {
  document.getElementById("modalSoquete").style.display = "none";
  document.body.style.overflow = ""; // destrava o fundo
});

//Chipset
document.getElementById("closeChipset").addEventListener("click", function() {
  document.getElementById("modalChipset").style.display = "none";
  document.body.style.overflow = ""; // destrava o fundo
});

//RAM
document.getElementById("closeRAM").addEventListener("click", function(){
  document.getElementById("modalRAM").style.display = "none";
  document.body.style.overflow = ""; // destrava o fundo
});

// SSD NVMe
document.getElementById("closeNVMe").addEventListener("click", function() {
  document.getElementById("modalNVMe").style.display = "none";
  document.body.style.overflow = ""; // destrava o fundo
});

// SSD SATA
document.getElementById("closeSATA").addEventListener("click", function() {
  document.getElementById("modalSATA").style.display = "none";
  document.body.style.overflow = ""; // destrava o fundo
});

// Fechar modais ao clicar fora do conteúdo
window.addEventListener("click", function(e) {
  if (e.target.classList.contains("modal-hardware")) {
    e.target.style.display = "none";
    document.body.style.overflow = ""; // destrava o fundo
  }
});






