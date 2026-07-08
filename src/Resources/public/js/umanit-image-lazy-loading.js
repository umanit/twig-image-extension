/**
 * Powers the optional blur-up effect on images rendered by the `*_lazy_load`
 * Twig helpers when the `blur` option is enabled.
 *
 * Lazy loading itself is handled natively by the browser through the
 * `loading="lazy"` attribute, so no IntersectionObserver or third-party
 * library is required. This listener only removes the blur once the real
 * image has finished loading.
 *
 * The `load` event does not bubble, hence the capture phase (`true`).
 *
 * @param {(event: Event) => void} [loadEventCallback] Optional callback invoked for each loaded image.
 */
const umanitImageLazyLoad = loadEventCallback => {
  document.addEventListener(
    'load',
    event => {
      const img = event.target;

      if ('IMG' !== img.nodeName) {
        return;
      }

      if (img.classList.contains('lazy-blur')) {
        img.classList.add('lazy-loaded');
      }

      if (loadEventCallback) {
        loadEventCallback(event);
      }
    },
    true,
  );
};

export default umanitImageLazyLoad;