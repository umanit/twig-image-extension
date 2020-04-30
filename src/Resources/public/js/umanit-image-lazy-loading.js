const umanitImageLazyLoad = (yall, loadEventCallback) => {
  document.addEventListener('DOMContentLoaded', () => {
    yall({
      events: {
        load: event => {
          if (!event.target.classList.contains('lazy') && 'IMG' === event.target.nodeName) {
            event.target.classList.remove('lazy-placeholder');
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
