import Alpine from 'alpinejs';
import { drawIcons } from './icons';

window.Alpine = Alpine;

document.addEventListener('alpine:initialized', () => drawIcons());

Alpine.start();
drawIcons();
