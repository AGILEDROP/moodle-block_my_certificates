// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('block_my_certificates/pdf_preview', [], function() {
  'use strict';

  /**
   * Render a PDF on canvas.
   * @param {HTMLCanvasElement} canvas The canvas element
   * @param {string} workerSrc The worker source URL
   */
  async function renderCanvas(canvas, workerSrc) {
      const url = canvas.dataset.src;
      const pageN = 1;
      const targetWidth = 900;

      window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

      const pdf = await window.pdfjsLib.getDocument(url).promise;
      const page = await pdf.getPage(pageN);
      const baseViewport = page.getViewport({scale: 1});
      const scale = targetWidth / baseViewport.width;
      const viewport = page.getViewport({scale});

      canvas.width = viewport.width;
      canvas.height = viewport.height;

      const ctx = canvas.getContext('2d');

      await page.render({
          canvasContext: ctx,
          viewport: viewport
      }).promise;
  }

  /**
   * Initialize PDF rendering for all .pdf-canvas elements.
   * @param {Object} args Arguments with workersrc property
   */
  async function init(args) {
      const workerSrc = args.workersrc;
      const nodes = document.querySelectorAll('.pdf-canvas');
      for (const node of nodes) {
          await renderCanvas(node, workerSrc);
      }
  }

  return {init: init};
});