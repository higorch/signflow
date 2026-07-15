import * as pdfjsLib from "pdfjs-dist/build/pdf";

window.pdfjsLib = pdfjsLib;

pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.bundle.js';