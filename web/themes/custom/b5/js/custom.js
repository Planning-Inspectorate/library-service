(function (Drupal) {
  'use strict';

  Drupal.behaviors.customBehavior = {
    // This function is called when the behavior is attached to the DOM.
    attach: function (context) {
      const link = context.querySelector('.show-filters');
      const wrapper = context.querySelector('.extra-filters');

      if (!link || !wrapper) {
        return;
      }

      // Initialize the wrapper state based on localStorage.
      this.initWrapperState(wrapper, link);
      // Remove any existing click event listeners and add a new one.
      link.removeEventListener('click', this.handleClick);
      link.addEventListener('click', this.handleClick);
    },

    // This function initializes the wrapper state based on the value stored in localStorage.
    initWrapperState: function (wrapper, link) {
      const wrapperState = localStorage.getItem('wrapper-state');
      wrapper.classList.toggle('d-none', wrapperState !== 'visible');
      link.classList.toggle('closed', wrapperState !== 'visible');
    },

    // This function handles the click event for the show-filters link.
    handleClick: function (event) {
      event.preventDefault();

      const wrapper = document.querySelector('.extra-filters');
      const link = document.querySelector('.show-filters');

      // Toggle the visibility of the wrapper and the link's 'closed' class.
      wrapper.classList.toggle('d-none');
      link.classList.toggle('closed');

      // Update the wrapper state in localStorage.
      const newState = wrapper.classList.contains('d-none') ? 'hidden' : 'visible';
      localStorage.setItem('wrapper-state', newState);
    },
  };
  
})(Drupal);