<?php
// faq.php
require_once 'includes/header.php';
?>

<div class="container py-5 animate-fade-in">
    <div class="text-center mb-5">
        <h1 class="fw-bold mb-3 display-4 text-white">Nexus Support Centre</h1>
        <p class="text-muted fs-5">Everything you need to know about navigating the market.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="accordion accordion-flush glass-card p-4 border-0 shadow-lg" id="faqAccordion">
                
                <!-- FAQ Item 1 -->
                <div class="accordion-item bg-transparent border-bottom border-light border-opacity-10 py-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-5 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I complete a purchase?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted opacity-75">
                            Once you've added items to your cart, navigate to the <strong>Checkout</strong> page. You'll enter your shipping address and secure payment details. Our global payment gateway supports all major credit/debit cards and PayPal for 256-bit encrypted transactions.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="accordion-item bg-transparent border-bottom border-light border-opacity-10 py-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-5 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Can I change my order quantity at checkout?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted opacity-75">
                            Yes! Our new interactive checkout allows you to use <strong>Batch Controls (+/- buttons)</strong> next to each item to adjust quantities in real-time. Your total payable amount will update automatically as you make changes.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="accordion-item bg-transparent border-bottom border-light border-opacity-10 py-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-5 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            What happens after I cancel an order?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted opacity-75">
                            If your order is still "Pending," you can cancel it via the <strong>My Orders</strong> page. The status will update to "Cancelled," and your items will be automatically returned to the global stock inventory. You also have the option to <strong>Reorder</strong> a cancelled transaction with a single click!
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="accordion-item bg-transparent border-bottom border-light border-opacity-10 py-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-5 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            How do I track my order's status?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted opacity-75">
                            Navigate to <strong>My Orders</strong> in the top menu. Each purchase features an <strong>Immediate Status Tracker</strong> that shows whether your order is Pending, Dispatched, or Completed.
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="accordion-item bg-transparent border-0 py-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-5 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Is my card information secure?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted opacity-75">
                            Absolutely. Nexus Market uses advanced validation to verify card numbers, expiry dates, and CVVs server-side. All processing is conducted through secure simulations designed to mimic industry-leading 256-bit AES encryption.
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="text-center mt-5">
                <p class="text-muted small">Still have questions? <a href="mailto:support@nexus.market" class="text-primary text-decoration-none fw-bold">Contact Our Support Team</a></p>
            </div>
        </div>
    </div>
</div>

<style>
    .accordion-button:after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
    }
    .accordion-button:not(.collapsed) {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: var(--bs-primary) !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>
