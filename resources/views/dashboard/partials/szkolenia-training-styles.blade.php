.training-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.training-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #f8f9fa;
    transition: box-shadow 0.2s ease;
}
.training-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.training-content {
    flex: 1;
}
.training-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #212529;
}
.training-title-link {
    text-decoration: none;
    transition: color 0.2s ease;
}
/* Aktywny dostęp: tytuł + ikona PLAY / folder w niebieskim */
.training-title-link--has-access {
    color: #0d6efd;
}
.training-title-link--has-access:hover {
    color: #0a58ca;
}
.training-title-link--has-access .training-title-folder-icon--leading {
    color: #0d6efd;
    opacity: 1;
}
.training-title-link--disabled {
    cursor: not-allowed;
    color: #6c757d !important;
}
.training-title-link--disabled:hover {
    color: #6c757d !important;
}
.training-title-link--expired .training-title-folder-icon--leading {
    color: #6c757d;
    opacity: 1;
}
.training-title-link--expired-notes {
    color: #6c757d;
}
.training-title-link--expired-notes:hover {
    color: #495057;
}
.training-title-link--expired-notes .training-title-play-badge--disabled {
    background: #6c757d;
}
/* Wyraźny „PLAY” przy tytule, gdy jest nagranie wideo */
.training-title-play-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 1px 4px rgba(13, 110, 253, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.training-title-link--has-access:hover .training-title-play-badge {
    transform: scale(1.07);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.45);
}
.training-title-play-badge .bi-play-fill {
    font-size: 1rem;
    margin-left: 2px;
}
/* PLAY / folder przed tytułem — odstęp jak spacja po ikonie */
.training-title-play-badge--leading {
    margin-right: 0.4rem;
    margin-left: 0;
    vertical-align: middle;
    position: relative;
    top: -0.05em;
}
.training-title-folder-icon--leading {
    margin-right: 0.4rem;
    font-size: 0.95em;
    vertical-align: middle;
}
.training-title-play-badge--disabled {
    background: #adb5bd;
    box-shadow: none;
}
.training-access-term {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}
.training-notes-indicator__link {
    color: #0d6efd;
    font-weight: 500;
}
.training-notes-indicator__link:hover {
    color: #0a58ca;
    text-decoration: underline !important;
}
.training-meta {
    font-size: 0.95rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.training-date--upcoming .training-date__value {
    color: #198754;
    font-weight: 700;
}
.training-separator {
    color: #adb5bd;
}
.training-materials {
    border-color: #e9ecef !important;
}
.training-material-link {
    color: #0d6efd;
    word-break: break-word;
}
.training-material-link:hover {
    color: #0a58ca;
    text-decoration: underline !important;
}
.training-material-link__label {
    vertical-align: middle;
}
.training-certificate {
    margin-left: 1.5rem;
    flex-shrink: 0;
}
.certificate-download-link {
    display: inline-block;
    text-decoration: none;
    transition: all 0.3s ease;
}
.certificate-download-link--disabled {
    cursor: help;
}
.certificate-download-link--disabled:hover .certificate-icon {
    transform: none;
}
.certificate-icon {
    width: 250px;
    height: auto;
    display: block;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
}
.certificate-download-link:hover .certificate-icon {
    transform: scale(1.1);
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
}
.certificate-icon--muted {
    opacity: 0.5;
    filter: grayscale(0.4) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.12));
}
.certificate-download-link--disabled:hover .certificate-icon--muted {
    filter: grayscale(0.4) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.12));
}
.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #0d6efd;
    border-color: #dee2e6;
}
.pagination .page-link:hover {
    color: #0a58ca;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
    opacity: 0.5;
}
@media (max-width: 767.98px) {
    .training-item {
        flex-direction: column;
        align-items: stretch;
    }
    .training-certificate {
        margin-left: 0;
        margin-top: 1rem;
        text-align: center;
    }
    .certificate-icon {
        width: 200px;
    }
}

.training-live-access {
    background: #e7f1ff;
}
.training-live-countdown {
    font-variant-numeric: tabular-nums;
}
