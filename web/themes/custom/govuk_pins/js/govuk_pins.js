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
  console.log('acceptCookiePolicy');
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie('cookie_type', 'accept', 90);
}

function rejectCookiePolicy () {
  console.log('rejectCookiePolicy');
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie('cookie_type', 'reject', 90);
}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function add_blank_target_to_pdf_link() {
  //check if period is inside the link
  jQuery("a").each(function(index, valu) {
    var link_text = jQuery(this).text();
    var last_char = link_text.substring(link_text.length - 1);

    if(last_char == '.'){
      link_text =  link_text.substring(0,link_text.length - 1);
      jQuery(this).text(link_text);
      var link_text_html =  jQuery(this).parent().html();
      jQuery(this).parent().html(link_text_html+last_char);
    }
  });

  if (jQuery(".view-document").is(":visible")) {
    $href = jQuery(".view-document span.file ").find('a');
    
    // $link_text = $txt;
    $href.prop('target', '_blank');
    $href.prop('rel', 'noreferrer noopener');
    $href.addClass('govuk-link');
    //add target and external link accessibility text
    $href.append(' (opens in new tab)<span class="fa-ext extlink"><span class="fa fa-external-link" aria-label="(link is external)"></span></span>');
  }
}

function bef_datepicker_format_change() {
  jQuery('.bef-datepicker').each(function () {
    jQuery(this).datepicker({ dateFormat: 'dd-mm-yy' }); 
  });
}

bef_datepicker_format_change();
add_blank_target_to_pdf_link();
