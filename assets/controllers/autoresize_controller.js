import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        placeholder: String
    }

    connect() {
        this.resize();
        this.checkIfEmpty();
        this.resizeHandler = this.resize.bind(this);
        window.addEventListener('resize', this.resizeHandler);
    }

    disconnect() {
        window.removeEventListener('resize', this.resizeHandler);
    }

    input(event) {
        this.resize();
        this.checkIfEmpty();
    }

    resize() {
        this.element.style.height = 'auto';
        this.element.style.height = `${this.element.scrollHeight}px`;
    }

    handleFocusIn() {
        this.element.classList.add('focused');
    }

    handleFocusOut() {
        this.element.classList.remove('focused');
        this.checkIfEmpty();
    }

    checkIfEmpty() {
        const isEmpty = this.element.textContent.trim().length === 0;
        console.log(`Element is empty: ${isEmpty}`);
        if (isEmpty) {
            this.element.classList.add('empty');
        } else {
            this.element.classList.remove('empty');
        }
    }
}