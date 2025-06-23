import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect(){
        this.element.addEventListener('click', this.toggle.bind(this));
    }
    toggle(event) {
        event.preventDefault();
        const menu = document.querySelector('nav');
        const overlay = document.createElement('div');
        overlay.className = 'nav-overlay';
        document.body.appendChild(overlay);
        overlay.addEventListener('click', function() {
            menu.classList.remove('show-nav-left');
            overlay.classList.remove('show');
        });

        if (menu) {
            overlay.classList.toggle('show');
            menu.classList.toggle('show-nav-left');
        }
    }
}