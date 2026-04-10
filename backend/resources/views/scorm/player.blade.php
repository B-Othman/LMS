<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SCORM Player</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: #000; overflow: hidden; }
  #scorm-frame {
    position: fixed; inset: 0; width: 100%; height: 100%;
    border: none; display: block;
  }
</style>
</head>
<body>
<iframe
  id="scorm-frame"
  src="{{ route('scorm.asset', ['sessionId' => $session->id, 'path' => $sco_url]) }}"
  allow="fullscreen"
  sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-pointer-lock allow-downloads"
></iframe>

<script>
(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────────────
     Configuration injected by the server
  ───────────────────────────────────────────────────────────────── */
  var SESSION_ID    = {{ $session->id }};
  var API_BASE      = "{{ $api_base }}";
  var SESSION_TOKEN = "{{ $session_token }}";

  /* ─────────────────────────────────────────────────────────────────
     In-memory CMI data store
  ───────────────────────────────────────────────────────────────── */
  var _cmiData   = {};
  var _initialized = false;
  var _finished    = false;
  var _lastError   = '0';

  var ERROR_STRINGS = {
    '0':   'No error',
    '101': 'General exception',
    '201': 'Invalid argument error',
    '202': 'Element cannot have children',
    '203': 'Element not an array – cannot have count',
    '301': 'Not initialized',
    '401': 'Not implemented error',
    '402': 'Invalid set value, element is a keyword',
    '403': 'Element is read only',
    '404': 'Element is write only',
    '405': 'Incorrect data type',
  };

  /* ─────────────────────────────────────────────────────────────────
     HTTP helpers
  ───────────────────────────────────────────────────────────────── */
  function apiPost(endpoint, body) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', API_BASE + endpoint, false); // synchronous — required by SCORM 1.2 spec
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');
    if (SESSION_TOKEN) {
      xhr.setRequestHeader('Authorization', 'Bearer ' + SESSION_TOKEN);
    }
    try {
      xhr.send(JSON.stringify(body));
    } catch (e) {
      console.error('[SCORM RTE] API call failed:', e);
    }
  }

  /* ─────────────────────────────────────────────────────────────────
     SCORM 1.2 Runtime API (API_1484_11 on window)
  ───────────────────────────────────────────────────────────────── */
  window.API = {

    LMSInitialize: function (param) {
      if (_initialized) {
        _lastError = '101';
        return 'false';
      }
      _initialized = true;
      _lastError = '0';
      return 'true';
    },

    LMSFinish: function (param) {
      if (!_initialized) {
        _lastError = '301';
        return 'false';
      }
      _finished = true;

      apiPost('/scorm/sessions/' + SESSION_ID + '/finish', { cmi_data: _cmiData });

      // Notify parent frame (LMS) that the SCO has finished
      try { window.parent.postMessage({ type: 'scorm:finish', sessionId: SESSION_ID }, '*'); } catch (e) {}

      _initialized = false;
      _lastError = '0';
      return 'true';
    },

    LMSGetValue: function (element) {
      if (!_initialized) {
        _lastError = '301';
        return '';
      }
      _lastError = '0';
      var value = _cmiData[element];
      return (value !== undefined && value !== null) ? String(value) : '';
    },

    LMSSetValue: function (element, value) {
      if (!_initialized) {
        _lastError = '301';
        return 'false';
      }

      // Guard read-only elements
      var readOnly = [
        'cmi.core.student_id',
        'cmi.core.student_name',
        'cmi.core.credit',
        'cmi.core.entry',
        'cmi.core.lesson_mode',
        'cmi.launch_data',
        'cmi.comments_from_lms',
      ];

      if (readOnly.indexOf(element) !== -1) {
        _lastError = '403';
        return 'false';
      }

      _cmiData[element] = value;
      _lastError = '0';
      return 'true';
    },

    LMSCommit: function (param) {
      if (!_initialized) {
        _lastError = '301';
        return 'false';
      }
      apiPost('/scorm/sessions/' + SESSION_ID + '/commit', { cmi_data: _cmiData });
      _lastError = '0';
      return 'true';
    },

    LMSGetLastError: function () {
      return _lastError;
    },

    LMSGetErrorString: function (errorCode) {
      return ERROR_STRINGS[String(errorCode)] || 'Unknown error';
    },

    LMSGetDiagnostic: function (errorCode) {
      return ERROR_STRINGS[String(errorCode)] || 'No diagnostic available';
    },
  };

  /* ─────────────────────────────────────────────────────────────────
     Load initial CMI data from the iframe once it can communicate,
     then populate _cmiData so LMSGetValue works immediately.
     (The server pre-populates fields in the session state; we fetch
     them via a GET on /scorm/sessions/{id} before the SCO calls
     LMSInitialize.)
  ───────────────────────────────────────────────────────────────── */
  (function loadInitialState() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', API_BASE + '/scorm/sessions/' + SESSION_ID + '/state', false);
    xhr.setRequestHeader('Accept', 'application/json');
    if (SESSION_TOKEN) {
      xhr.setRequestHeader('Authorization', 'Bearer ' + SESSION_TOKEN);
    }
    try {
      xhr.send(null);
      if (xhr.status === 200) {
        var resp = JSON.parse(xhr.responseText);
        if (resp && resp.data && resp.data.cmi_data) {
          _cmiData = resp.data.cmi_data;
        }
      }
    } catch (e) {
      console.warn('[SCORM RTE] Could not load initial state:', e);
    }
  }());

  /* ─────────────────────────────────────────────────────────────────
     Ensure LMSFinish is called when the page unloads (safety net).
  ───────────────────────────────────────────────────────────────── */
  window.addEventListener('beforeunload', function () {
    if (_initialized && !_finished) {
      window.API.LMSFinish('');
    }
  });

}());
</script>
</body>
</html>
