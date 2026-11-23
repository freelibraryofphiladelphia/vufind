/*global VuFind, Cookies */

VuFind.register('siteBanners', function SiteBanners() {
  var cookiePrefix = 'banner-alert-';
  var cookieLifetimeDays = 1;
  var allowedTypes = ['danger', 'info', 'success', 'warning'];

  /**
   * Return the API's active announcement window in the format it accepts.
   * @returns {string} Current UTC timestamp.
   */
  function getCurrentTimestamp() {
    return new Date().toISOString().replace('T', ' ').replace('Z', '');
  }

  /**
   * Return a safe Bootstrap alert type.
   * @param {object} announcement API announcement.
   * @returns {string} Alert type.
   */
  function getAlertType(announcement) {
    var type = announcement.type && typeof announcement.type.name === 'string'
      ? announcement.type.name.toLowerCase()
      : 'info';
    return allowedTypes.indexOf(type) === -1 ? 'info' : type;
  }

  /**
   * Set a 24-hour dismissal cookie for the current host.
   * @param {string} id Announcement ID.
   */
  function setDismissalCookie(id) {
    var domain = window.location.hostname.match(/(?:^|\.)freelibrary\.org$/)
      ? '.freelibrary.org'
      : undefined;
    var options = { expires: cookieLifetimeDays, path: '/', sameSite: 'Lax' };
    if (domain) {
      options.domain = domain;
    }
    Cookies.set(cookiePrefix + id, 'notice-from-library', options);
  }

  /**
   * Create one alert from an API announcement.
   * @param {object} announcement API announcement.
   * @returns {HTMLElement|null} Alert element, or null for invalid records.
   */
  function createAlert(announcement) {
    if (!announcement || !/^[A-Za-z0-9_-]+$/.test(String(announcement.id))) {
      return null;
    }

    var id = String(announcement.id);
    var alert = document.createElement('div');
    var bannerType = getAlertType(announcement);
    alert.className = 'alert alert-' + bannerType + ' alert-global alert-notice header-notices';
    if (bannerType === 'danger') {
      alert.classList.remove('alert-notice');
      alert.classList.add('alert-attention');
    }
    alert.id = 'alertBlock-' + id;
    alert.setAttribute('role', 'alert');

    var wrapper = document.createElement('div');
    wrapper.className = 'container-fluid';
    var message = document.createElement('div');
    message.innerHTML = announcement.announcement_text || '';
    wrapper.appendChild(message);
    alert.appendChild(wrapper);

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'close';
    close.id = 'alertMsg-' + id;
    close.setAttribute('aria-label', 'Close');

    var closeIcon = document.createElement('span');
    closeIcon.setAttribute('aria-hidden', 'true');
    closeIcon.textContent = '\u00d7';
    close.appendChild(closeIcon);
    close.addEventListener('click', function dismissBanner() {
      setDismissalCookie(id);
      alert.remove();
    });
    message.insertBefore(close, message.firstChild);
    return alert;
  }

  /**
   * Load and render active site announcements.
   * @returns {void}
   */
  function init() {
    var container = document.getElementById('site-alert-container');
    if (!container) {
      return;
    }

    var timestamp = getCurrentTimestamp();
    var query = new URLSearchParams({
      expired_after: timestamp,
      published_before: timestamp
    });

    fetch('/assets/json/banner.json?' + query.toString(), { credentials: 'same-origin' })
      .then(function checkResponse(response) {
        if (!response.ok) {
          throw new Error('Banner request failed');
        }
        return response.json();
      })
      .then(function renderAnnouncements(response) {
        var announcements = response && Array.isArray(response.data) ? response.data : [];
        announcements.forEach(function renderAnnouncement(announcement) {
          var id = announcement && announcement.id;
          if (id && !Cookies.get(cookiePrefix + id)) {
            var alert = createAlert(announcement);
            if (alert) {
              container.appendChild(alert);
            }
          }
        });
      })
      .catch(function ignoreBannerFailure() {
        // Site banners are optional and must not interfere with page rendering.
      });
  }

  return { init: init };
});
