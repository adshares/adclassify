import $ from 'jquery'
import 'bootstrap/js/dist/alert'
// import 'bootstrap/js/dist/collapse.js';
import 'bootstrap/js/dist/dropdown'
import 'bootstrap/js/dist/popover'
// import 'bootstrap/js/dist/modal.js';
import '@fortawesome/fontawesome-free/js/fontawesome'
import '@fortawesome/fontawesome-free/js/regular'
import '@fortawesome/fontawesome-free/js/solid'
import '@fortawesome/fontawesome-free/css/fontawesome.css'
import '@fortawesome/fontawesome-free/css/regular.css'
import '@fortawesome/fontawesome-free/css/solid.css'
import './button-checkbox'

// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
// require('bootstrap');

$(document).ready(function () {
  $('[data-toggle="popover"]').popover()
  $('.button-checkbox').buttonCheckbox();
})

console.log('Hello Webpack Encore! Edit me in assets/js/app.js')
