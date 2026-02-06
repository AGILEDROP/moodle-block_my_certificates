PDF.js
========

This library is provided as part of the My Certificates block.

License: Apache License 2.0
Upstream: https://github.com/mozilla/pdf.js

Source:
  - Download a stable release of pdfjs-dist from:
    https://github.com/mozilla/pdf.js/releases

Build/Packaging:
  - Use the prebuilt `pdf.min.js` and `pdf.worker.min.js` from the
    pdfjs-dist package.
  - Copy them into:
      blocks/my_certificates/thirdparty/pdfjs/

Local modifications:
  - None.
