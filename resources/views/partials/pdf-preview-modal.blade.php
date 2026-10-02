{{-- Modal Pop-up PDF Reviewer --}}
<div id="pdfPreviewModal"
     class="fixed inset-0 z-50 hidden transition-opacity duration-200"
     role="dialog"
     aria-modal="true"
     aria-labelledby="pdfModalTitle">

    {{-- Backdrop --}}
    <div id="pdfModalBackdrop"
         onclick="closePdfPreviewModal()"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

    {{-- Modal Dialog --}}
    <div class="fixed inset-0 z-10 flex items-center justify-center p-3 sm:p-6 overflow-hidden">
        <div class="relative flex flex-col w-full max-w-5xl h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">

            {{-- Modal Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 bg-slate-50/90">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="pdfModalTitle" class="text-sm font-bold text-slate-900">
                            Pratinjau PDF
                        </h3>
                        <p id="pdfModalSubtitle" class="text-[11px] font-mono text-slate-500 font-medium">
                            Kode: <span id="pdfModalSurveyCode">-</span>
                        </p>
                    </div>
                </div>

                {{-- Section Selector Tabs --}}
                <div class="flex items-center rounded-lg bg-slate-200/70 p-1 text-xs font-semibold">
                    <button type="button"
                            id="pdfTabChecklist"
                            onclick="switchPdfSection('checklist')"
                            class="rounded-md px-3 py-1 text-slate-700 transition cursor-pointer">
                        Lembar Ceklist
                    </button>
                    <button type="button"
                            id="pdfTabReport"
                            onclick="switchPdfSection('report')"
                            class="rounded-md px-3 py-1 text-slate-700 transition cursor-pointer">
                        Laporan Evaluasi
                    </button>
                </div>

                {{-- Action Controls --}}
                <div class="flex items-center gap-2">
                    <a id="pdfModalDownloadBtn"
                       href="#"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Unduh PDF</span>
                    </a>

                    <a id="pdfModalNewTabBtn"
                       href="#"
                       target="_blank"
                       title="Buka pratinjau di tab baru"
                       class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-50 shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <button type="button"
                            onclick="closePdfPreviewModal()"
                            title="Tutup (Esc)"
                            class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-50 shadow-sm transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body: PDF Reviewer / Iframe --}}
            <div class="relative flex-1 bg-slate-100 min-h-0">
                {{-- Loading Spinner --}}
                <div id="pdfModalLoader" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50 text-slate-500 z-10">
                    <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-medium">Memuat pratinjau dokumen PDF...</span>
                </div>

                <iframe id="pdfModalIframe"
                        src="about:blank"
                        class="w-full h-full border-0"
                        title="Pratinjau PDF Reviewer">
                </iframe>
            </div>

            {{-- Fallback Footer --}}
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-2 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Gunakan kontrol di dalam penampil PDF untuk zoom, cetak, atau navigasi halaman.</span>
                <span class="text-slate-400">Tekan <kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[10px]">Esc</kbd> untuk menutup</span>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPdfBaseUrl = '';
    let currentPdfSection = 'checklist';

    function openPdfPreviewModal(baseUrl, surveyCode, initialSection = 'checklist') {
        currentPdfBaseUrl = baseUrl;
        currentPdfSection = initialSection;

        const modal = document.getElementById('pdfPreviewModal');
        const codeElem = document.getElementById('pdfModalSurveyCode');
        const loader = document.getElementById('pdfModalLoader');

        if (codeElem) {
            codeElem.textContent = surveyCode || '-';
        }

        if (loader) {
            loader.classList.remove('hidden');
        }

        updatePdfTabsUI();
        loadPdfIframe();

        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }

    function switchPdfSection(section) {
        if (currentPdfSection === section) return;
        currentPdfSection = section;

        const loader = document.getElementById('pdfModalLoader');
        if (loader) {
            loader.classList.remove('hidden');
        }

        updatePdfTabsUI();
        loadPdfIframe();
    }

    function updatePdfTabsUI() {
        const tabChecklist = document.getElementById('pdfTabChecklist');
        const tabReport = document.getElementById('pdfTabReport');

        const activeClasses = ['bg-white', 'text-blue-700', 'shadow-sm'];
        const inactiveClasses = ['text-slate-600', 'hover:text-slate-900'];

        if (tabChecklist && tabReport) {
            if (currentPdfSection === 'checklist') {
                tabChecklist.classList.add(...activeClasses);
                tabChecklist.classList.remove(...inactiveClasses);
                tabReport.classList.remove(...activeClasses);
                tabReport.classList.add(...inactiveClasses);
            } else {
                tabReport.classList.add(...activeClasses);
                tabReport.classList.remove(...inactiveClasses);
                tabChecklist.classList.remove(...activeClasses);
                tabChecklist.classList.add(...inactiveClasses);
            }
        }
    }

    function loadPdfIframe() {
        if (!currentPdfBaseUrl) return;

        const url = new URL(currentPdfBaseUrl, window.location.origin);
        url.searchParams.set('section', currentPdfSection);
        url.searchParams.delete('download');

        const downloadUrl = new URL(url.toString());
        downloadUrl.searchParams.set('download', '1');

        const iframe = document.getElementById('pdfModalIframe');
        const downloadBtn = document.getElementById('pdfModalDownloadBtn');
        const newTabBtn = document.getElementById('pdfModalNewTabBtn');

        if (downloadBtn) {
            downloadBtn.href = downloadUrl.toString();
        }

        if (newTabBtn) {
            newTabBtn.href = url.toString();
        }

        if (iframe) {
            iframe.onload = function() {
                const loader = document.getElementById('pdfModalLoader');
                if (loader) {
                    loader.classList.add('hidden');
                }
            };
            iframe.src = url.toString();
        }
    }

    function closePdfPreviewModal() {
        const modal = document.getElementById('pdfPreviewModal');
        const iframe = document.getElementById('pdfModalIframe');

        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        if (iframe) {
            iframe.src = 'about:blank';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('pdfPreviewModal');
            if (modal && !modal.classList.contains('hidden')) {
                closePdfPreviewModal();
            }
        }
    });
</script>
