(() => {
  const externalUserId = webtonative_push_notification_settings.external_user_id;
  if (!window.WTN) {
    return;
  }
  const WTN = window.WTN;
  WTN.OneSignal.setExternalUserId(externalUserId);
})();
