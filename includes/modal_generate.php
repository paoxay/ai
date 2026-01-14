<style>
    .upload-container {
        border: 2px dashed #475569;
        border-radius: 12px;
        background: rgba(30, 41, 59, 0.4);
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        position: relative;
    }
    .upload-container:hover {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }
    .upload-icon { font-size: 40px; color: #94a3b8; margin-bottom: 10px; }
    
    /* Grid ສະແດງຮູບ Preview */
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    .preview-item {
        position: relative;
        padding-top: 100%; /* ສີ່ຫຼ່ຽມ */
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #475569;
        background: #000;
    }
    .preview-item img {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
    }
    .btn-remove-img {
        position: absolute; top: 2px; right: 2px;
        background: red; color: white;
        border: none; border-radius: 50%;
        width: 20px; height: 20px;
        font-size: 10px; line-height: 20px;
        cursor: pointer; z-index: 10;
    }
</style>

<div class="modal fade" id="genModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">✨ ຕັ້ງຄ່າ: <span id="modalTitle" class="text-primary fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="aiForm" enctype="multipart/form-data">
                    <input type="hidden" name="template_id" id="tplId">

                    <div id="dynamicFieldsContainer" class="mb-4"></div>

                    <div class="mb-4">
                        <label class="form-label text-info small fw-bold">ອັດຕາສ່ວນຮູບພາບ</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="aspect_ratio" id="ar1" value="1:1" checked>
                                <label class="btn btn-outline-secondary w-100 py-2" for="ar1">1:1 (ສີ່ຫຼ່ຽມ)</label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="aspect_ratio" id="ar2" value="4:5">
                                <label class="btn btn-outline-secondary w-100 py-2" for="ar2">4:5 (Story)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-lg">
                        <i class="fas fa-bolt me-2"></i> ສ້າງທັນທີ (<span id="modalPrice"></span> Pts)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadingModal" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white p-5 text-center border-secondary">
            <div class="spinner-ai mb-4"></div>
            <h4>ກຳລັງປະມວນຜົນ...</h4>
            <p class="text-white-50 mb-0">AI ກຳລັງວາດຮູບໃຫ້ທ່ານ...</p>
        </div>
    </div>
</div>

<div class="modal fade" id="resultModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-center border-secondary">
            <div class="modal-header border-secondary justify-content-center">
                <h5 class="modal-title text-success">🎉 ສຳເລັດແລ້ວ!</h5>
            </div>
            <div class="modal-body p-0">
                <img id="resultImage" class="img-fluid w-100">
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
                <a id="downloadBtn" class="btn btn-success px-4" download>ດາວໂຫລດ</a>
            </div>
        </div>
    </div>
</div>