/* @license GPL-2.0-or-later https://www.drupal.org/licensing/faq */
document.addEventListener('DOMContentLoaded',initBurger);function initBurger(){const burger=document.querySelector('.mobile-buttons');const menu=document.getElementById('block-exam-navigation');burger.addEventListener('click',()=>{burger.classList.toggle('open');menu.classList.toggle('show');});};
