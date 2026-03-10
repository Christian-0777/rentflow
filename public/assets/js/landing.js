// Scroll animation for feature cards

window.addEventListener("scroll", function(){

const elements = document.querySelectorAll(".fade-in");

elements.forEach(el => {

const elementTop = el.getBoundingClientRect().top;

const windowHeight = window.innerHeight;

if(elementTop < windowHeight - 100){
el.classList.add("show");
}

});

});