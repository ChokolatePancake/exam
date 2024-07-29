document.addEventListener('DOMContentLoaded', initBurger)

function initBurger(){
  // Get references to the burger icon and menu elements
  const burger = document.querySelector('.mobile-buttons');
  const menu = document.getElementById('block-exam-navigation');

  // Add click event listener to the burger icon
  burger.addEventListener('click', ()=>{
    // Toggle 'open' class on burger icon for visual feedback
    burger.classList.toggle('open');
    // Toggle 'show' class on menu to display/hide it
    menu.classList.toggle('show');
  });
}
