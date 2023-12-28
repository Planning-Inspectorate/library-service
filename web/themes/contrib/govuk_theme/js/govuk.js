function toggleMobileMenu() {
  if (!jQuery("#mobile-menu").html()) {
    let content = jQuery(".govuk-header__navigation").parent(".app-navigation").html();
    let html = "<div id='mobile-menu-content'>" + content + "</div>";
    jQuery("#mobile-menu").html(html);
  }
  if (!jQuery("#mobile-menu-content").is(":visible")) {
    jQuery("#mobile-menu-content").slideDown();
  }
  else {
    jQuery("#mobile-menu-content").slideUp();
  }
}

function acceptCookiePolicy () {
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie('cookie_type', 'accept', 90);
}

function rejectCookiePolicy () {
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie('cookie_type', 'reject', 90);
}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}