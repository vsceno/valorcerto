import Alpine from 'alpinejs';
import simulador from './simulador';

window.Alpine = Alpine;

Alpine.data('simulador', simulador);

Alpine.start();
