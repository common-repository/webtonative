(() => {
  if (typeof window.WTN === 'undefined') {
    return;
  }

  const WTN = window.WTN;

  function generate_token(length) {
    var a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'.split('');
    var b = [];
    for (var i = 0; i < length; i++) {
      var j = (Math.random() * (a.length - 1)).toFixed(0);
      b[i] = a[j];
    }
    return b.join('');
  }

  function render() {
    let checked = localStorage.getItem('wtnBiometricLogin') === 'true';

    function setChecked(value) {
      checked = value;
    }

    const handleCheckedChange = (checked = false) => {
      localStorage.setItem('wtnBiometricLogin', checked);
      setChecked(checked);
      if (checked) {
        WTN.Biometric.saveSecret({
          secret: generate_token(50),
          callback: function (saved) {
            if (!saved.isSuccess) return;
            WTN.Biometric.show({
              prompt: 'Authenticate to continue!',
              callback: function (auth) {
                localStorage.setItem('biometricAuth', auth.secret);
              },
            });
          },
        });
        return;
      }
      WTN.Biometric.deleteSecret({
        callback: function (deleted) {
          if (!deleted.isSuccess) return;
          localStorage.removeItem('biometricAuth');
        },
      });
    };

    const init = () => {
      const isAlreadyLoggedIn = sessionStorage.getItem('wtnBiometricLogin') === 'true';
      if (!checked || isAlreadyLoggedIn) {
        return;
      }
      WTN.Biometric.show({
        prompt: 'Authenticate to continue!',
        callback: function (auth) {
          const secret = localStorage.getItem('biometricAuth');
          if (secret !== auth.secret) {
            WTN.closeApp();
            return;
          }
          sessionStorage.setItem('wtnBiometricLogin', 'true');
        },
      });
    };

    const input = document.createElement('input');
    const div = document.createElement('div');
    const label = document.createElement('label');
    const id = 'webtonative-input-checkbox';

    label.innerHTML = 'Enable biometric login';
    label.htmlFor = id;

    input.type = 'checkbox';
    input.id = id;
    input.checked = checked;
    input.onchange = (e) => {
      handleCheckedChange(e.target.checked);
    };

    div.appendChild(label);
    div.appendChild(input);

    init();

    return div;
  }

  WTN.Biometric.checkStatus({
    callback: function (data) {
      if (data.hasTouchId !== true) {
        return;
      }
      const container = document.getElementById('wton-biometric-wrapper');
      const component = render();
      container.appendChild(component);
    },
  });
})();
