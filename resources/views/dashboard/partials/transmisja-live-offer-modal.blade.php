{{-- Oferta kolejnego szkolenia — wyśrodkowane okienko Bootstrap (wewnątrz shella = działa też w fullscreen) --}}
<div class="modal fade" id="cmLiveOfferModal" tabindex="-1" aria-labelledby="cmLiveOfferModalLabel" aria-hidden="true"
     data-bs-backdrop="false"
     data-bs-keyboard="true"
     data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable cm-live-offer-modal__dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <p class="modal-title small text-uppercase text-muted fw-semibold mb-0" id="cmLiveOfferModalLabel">
                    <i class="bi bi-stars me-1" aria-hidden="true"></i>
                    Kolejne szkolenie
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="cm-live-offer-modal__image-wrap mb-3" data-live-offer-image-wrap hidden>
                    <img src=""
                         alt=""
                         class="cm-live-offer-modal__image img-fluid rounded"
                         data-live-offer-image
                         loading="lazy"
                         decoding="async">
                </div>
                <h2 class="h5 mb-2" data-live-offer-title></h2>
                <div class="cm-live-offer-modal__meta text-muted small mb-2">
                    <span class="d-inline-flex align-items-center gap-1 me-3" data-live-offer-date-wrap hidden>
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <span data-live-offer-date></span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1" data-live-offer-instructor-wrap hidden>
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <span data-live-offer-instructor></span>
                    </span>
                </div>
                <div class="cm-live-offer-modal__price mb-3" data-live-offer-price-wrap hidden>
                    <span class="cm-live-offer-modal__price-original text-muted text-decoration-line-through me-2"
                          data-live-offer-price-original
                          hidden></span>
                    <strong class="cm-live-offer-modal__price-current" data-live-offer-price-current></strong>
                    <span class="badge text-bg-danger ms-2" data-live-offer-promo-badge hidden>Promocja</span>
                    <div class="small text-muted mt-1" data-live-offer-promo-end hidden></div>
                    <div class="small text-muted" data-live-offer-omnibus hidden></div>
                </div>
                <p class="mb-0 text-body-secondary cm-live-offer-modal__description"
                   data-live-offer-description
                   hidden></p>
            </div>
            <div class="modal-footer border-0 pt-0 flex-column flex-sm-row gap-2 justify-content-center">
                <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
                    Zamknij
                </button>
                <span data-live-offer-desc-link-wrap hidden>
                    <a href="#"
                       class="btn btn-outline-primary w-100 w-sm-auto"
                       data-live-offer-desc-link
                       target="_blank"
                       rel="noopener noreferrer">
                        Opis szkolenia
                    </a>
                </span>
                <a class="btn btn-primary w-100 w-sm-auto"
                   data-live-offer-cta
                   href="#"
                   target="_blank"
                   rel="noopener noreferrer">
                    <i class="bi bi-cart-check me-1" aria-hidden="true"></i>
                    Zamawiam szkolenie
                </a>
            </div>
        </div>
    </div>
</div>
