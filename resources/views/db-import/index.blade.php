@extends('layouts.app')

@section('title', 'Importar Base de Datos')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="bi bi-database-up" style="color:#f59e0b; margin-right:10px;"></i>Importar Base de Datos
        </h1>
        <p class="page-subtitle">Sube un dump SQL para restaurar los datos en el servidor</p>
    </div>
</div>

{{-- ── Success banner ──────────────────────────────────────────────── --}}
@if(session('import_success'))
<div style="margin-bottom:20px; padding:16px 20px; background:rgba(72,199,142,0.1); border:1px solid rgba(72,199,142,0.25); border-radius:12px; display:flex; align-items:flex-start; gap:12px;">
    <i class="bi bi-check-circle-fill" style="color:#48c78e; font-size:20px; flex-shrink:0; margin-top:1px;"></i>
    <div>
        <p style="color:#48c78e; font-weight:600; margin:0 0 4px;">¡Importación exitosa!</p>
        <p style="color:rgba(255,255,255,0.6); font-size:13px; margin:0;">
            Se ejecutaron <strong style="color:var(--white);">{{ session('import_executed') }}</strong> sentencias INSERT
            desde <strong style="color:var(--white);">{{ session('import_filename') }}</strong>.
        </p>
    </div>
</div>
@endif

{{-- ── Error banner ─────────────────────────────────────────────────── --}}
@if(session('import_failed'))
<div style="margin-bottom:20px; padding:16px 20px; background:rgba(255,82,82,0.1); border:1px solid rgba(255,82,82,0.25); border-radius:12px;">
    <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:{{ count(session('import_errors', [])) ? '12px' : '0' }};">
        <i class="bi bi-x-circle-fill" style="color:#ff5252; font-size:20px; flex-shrink:0; margin-top:1px;"></i>
        <div>
            <p style="color:#ff5252; font-weight:600; margin:0 0 4px;">Importación fallida</p>
            <p style="color:rgba(255,255,255,0.6); font-size:13px; margin:0;">
                {{ session('import_message') }}
                ({{ session('import_executed', 0) }} sentencias completadas antes del error)
            </p>
        </div>
    </div>
    @if(count(session('import_errors', [])) > 0)
    <div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:12px; max-height:200px; overflow-y:auto;">
        @foreach(session('import_errors') as $err)
            <p style="color:rgba(255,180,180,0.8); font-size:12px; font-family:monospace; margin:0 0 4px;">{{ $err }}</p>
        @endforeach
    </div>
    @endif
</div>
@endif

@if($errors->any())
<div style="margin-bottom:20px; padding:14px 18px; background:rgba(255,82,82,0.1); border:1px solid rgba(255,82,82,0.25); border-radius:12px;">
    <p style="color:#ff5252; font-size:13px; margin:0;">{{ $errors->first() }}</p>
</div>
@endif

{{-- ── Upload card ──────────────────────────────────────────────────── --}}
<div class="client-table-card" style="max-width:680px; padding:32px 36px;">

    {{-- Warning notice --}}
    <div style="display:flex; align-items:flex-start; gap:12px; padding:14px 16px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:10px; margin-bottom:28px;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b; font-size:18px; flex-shrink:0; margin-top:1px;"></i>
        <div style="font-size:13px; color:rgba(255,255,255,0.7); line-height:1.6;">
            <strong style="color:#f59e0b;">Solo se ejecutan sentencias INSERT INTO.</strong>
            Las sentencias CREATE, DROP, ALTER, SET y similares son ignoradas automáticamente.
            La importación se realiza dentro de una transacción: si ocurre algún error se revierte todo.
        </div>
    </div>

    <form action="{{ route('db-import.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
        @csrf

        {{-- Drop zone --}}
        <div id="dropZone" style="
            border: 2px dashed rgba(245,158,11,0.35);
            border-radius: 14px;
            padding: 44px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(245,158,11,0.03);
            margin-bottom: 24px;
        ">
            <i class="bi bi-file-earmark-code" style="font-size:44px; color:rgba(245,158,11,0.5); display:block; margin-bottom:14px;"></i>
            <p style="color:var(--white); font-weight:600; font-size:16px; margin:0 0 6px;">Arrastra tu archivo .sql aquí</p>
            <p style="color:rgba(255,255,255,0.4); font-size:13px; margin:0 0 20px;">o haz clic para seleccionarlo</p>
            <label for="sql_file" style="
                display:inline-block;
                padding:8px 22px;
                background:rgba(245,158,11,0.12);
                border:1px solid rgba(245,158,11,0.3);
                border-radius:8px;
                color:#fbbf24;
                font-size:13px;
                font-weight:600;
                cursor:pointer;
                transition:all 0.2s;
            " id="chooseFileBtn">
                <i class="bi bi-folder2-open" style="margin-right:6px;"></i>Seleccionar archivo
            </label>
            <input type="file" name="sql_file" id="sql_file" accept=".sql" style="display:none;">
            <p id="fileNameDisplay" style="margin:14px 0 0; font-size:13px; color:#fbbf24; display:none;">
                <i class="bi bi-file-earmark-check" style="margin-right:5px;"></i>
                <span id="fileNameText"></span>
            </p>
        </div>

        {{-- Info grid --}}
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:24px;">
            <div style="padding:12px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; text-align:center;">
                <p style="color:rgba(255,255,255,0.35); font-size:11px; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 4px;">Formato</p>
                <p style="color:var(--white); font-size:13px; font-weight:600; margin:0;">.sql</p>
            </div>
            <div style="padding:12px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; text-align:center;">
                <p style="color:rgba(255,255,255,0.35); font-size:11px; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 4px;">Tamaño máx.</p>
                <p style="color:var(--white); font-size:13px; font-weight:600; margin:0;">50 MB</p>
            </div>
            <div style="padding:12px 14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; text-align:center;">
                <p style="color:rgba(255,255,255,0.35); font-size:11px; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 4px;">Transacción</p>
                <p style="color:#48c78e; font-size:13px; font-weight:600; margin:0;"><i class="bi bi-shield-check"></i> Segura</p>
            </div>
        </div>

        <button type="submit" class="btn-primary-action" id="submitBtn" style="width:100%; justify-content:center; padding:12px; font-size:15px; background:linear-gradient(135deg,#f59e0b,#d97706);" disabled>
            <i class="bi bi-cloud-upload"></i> Ejecutar Importación
        </button>
    </form>
</div>

{{-- ── How to create a dump ─────────────────────────────────────────── --}}
<div class="client-table-card" style="max-width:680px; margin-top:20px; padding:24px 28px;">
    <h3 style="font-size:14px; font-weight:600; color:var(--white); margin:0 0 16px; display:flex; align-items:center; gap:8px;">
        <i class="bi bi-terminal" style="color:rgba(255,255,255,0.4);"></i> ¿Cómo generar el dump en local?
    </h3>
    <p style="color:rgba(255,255,255,0.4); font-size:12px; margin:0 0 8px;">Solo inserts (datos, sin estructura):</p>
    <div style="background:rgba(0,0,0,0.3); border-radius:8px; padding:12px 16px; font-family:monospace; font-size:12px; color:#80cbc4; position:relative;">
        mysqldump -u root -p --no-create-info --skip-triggers nombre_db &gt; datos.sql
        <button onclick="navigator.clipboard.writeText('mysqldump -u root -p --no-create-info --skip-triggers nombre_db > datos.sql')"
            style="position:absolute; right:10px; top:10px; background:none; border:none; color:rgba(255,255,255,0.3); cursor:pointer; font-size:13px;"
            title="Copiar">
            <i class="bi bi-clipboard"></i>
        </button>
    </div>
    <p style="color:rgba(255,255,255,0.4); font-size:12px; margin:12px 0 8px;">O con phpMyAdmin: Exportar → Formato SQL → desmarcar "estructura", dejar solo "datos".</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input      = document.getElementById('sql_file');
    const dropZone   = document.getElementById('dropZone');
    const fileNameEl = document.getElementById('fileNameDisplay');
    const fileNameTx = document.getElementById('fileNameText');
    const submitBtn  = document.getElementById('submitBtn');
    const form       = document.getElementById('importForm');

    function setFile(file) {
        if (!file) return;
        if (!file.name.endsWith('.sql')) {
            fileNameTx.textContent = 'Solo se permiten archivos .sql';
            fileNameEl.style.color = '#ff5252';
            fileNameEl.style.display = 'block';
            submitBtn.disabled = true;
            return;
        }
        fileNameTx.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        fileNameEl.style.color = '#fbbf24';
        fileNameEl.style.display = 'block';
        submitBtn.disabled = false;
        dropZone.style.borderColor = 'rgba(245,158,11,0.7)';
        dropZone.style.background  = 'rgba(245,158,11,0.06)';
    }

    input.addEventListener('change', () => setFile(input.files[0]));
    dropZone.addEventListener('click', (e) => {
        if (e.target !== document.getElementById('chooseFileBtn') && !e.target.closest('label')) {
            input.click();
        }
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'rgba(245,158,11,0.8)';
        dropZone.style.background  = 'rgba(245,158,11,0.08)';
    });
    dropZone.addEventListener('dragleave', () => {
        if (!input.files[0]) {
            dropZone.style.borderColor = 'rgba(245,158,11,0.35)';
            dropZone.style.background  = 'rgba(245,158,11,0.03)';
        }
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (file) {
            // Inject file into input via DataTransfer
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFile(file);
        }
    });

    form.addEventListener('submit', () => {
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        submitBtn.disabled  = true;
    });
});
</script>

@endsection
