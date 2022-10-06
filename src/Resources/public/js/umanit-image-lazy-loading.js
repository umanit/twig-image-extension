const umanitImageLazyLoad = (yall, loadEventCallback) => {
  document.addEventListener('DOMContentLoaded', () => {
    yall({
      events: {
        load: event => {
          if (!event.target.classList.contains('lazy') && 'IMG' === event.target.nodeName) {
            event.target.classList.remove('lazy-placeholder');
            event.target.setAttribute('sizes', event.target.dataset.sizes);
          }

          if (loadEventCallback) {
            loadEventCallback(event);
          }
        },
      },
    });
  });
};

export default umanitImageLazyLoad;
