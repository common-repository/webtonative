(function () {
  if (!window.WTN.isIosApp) {
    var buttons = document.querySelectorAll(".wton-social-wrapper .btn-apple");
    var len = buttons !== null ? buttons.length : 0;
    for (var i = 0; i < len; i++) {
      buttons[i].remove();
    }
  }
  if (window.WTN.isNativeApp) {
    var buttons = document.getElementsByClassName("wton-social-wrapper");
    var len = buttons !== null ? buttons.length : 0;
    for (var i = 0; i < len; i++) {
      buttons[i].className += " webtonative-app";
    }
  }
})();
function wtonSocialLogin(appName) {
  function handleAppLogin(bundleId) {
    window.WTN.socialLogin[appName].login({
      callback: function (data) {
        if (data.isSuccess) {
          var param = "";
          if (appName == "apple") {
            param =
              "id_token=" +
              data.idToken +
              "&first_name=" +
              data.firstName +
              "&last_name=" +
              data.lastName +
              "&client_id=" +
              bundleId;
          } else if (appName == "google") {
            param = "id_token=" + data.idToken;
          } else if (appName == "facebook") {
            param = "access_token=" + data.accessToken;
          }
          window.location.href =
            wton_wp_site_url +
            "/wp-json/webtonative/social-login/verify/" +
            appName +
            "?" +
            param;
        }
      },
    });
  }

  if (!window.WTN.isNativeApp) {
    window.location.href =
      wton_wp_site_url + "/wp-json/webtonative/social-login/auth/" + appName;
    return;
  }
  window.WTN.deviceInfo().then(function (value) {
    if (value) {
      handleAppLogin(value.appId);
    }
  });
}
