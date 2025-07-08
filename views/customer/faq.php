<?php
include __DIR__ . '/../../inc/customer/customer-header.php';
?>
<div class="container py-5">
    <h2 class="mb-4">Frequently Asked Questions (FAQ)</h2>
    <div class="accordion" id="faqAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="faq1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                    How do I place an order?
                </button>
            </h2>
            <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Browse our catalogue, add items to your cart, and proceed to checkout. Follow the on-screen instructions to complete your order.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="faq2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                    What payment methods are accepted?
                </button>
            </h2>
            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    We currently only accept Cash on Delivery (COD).
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="faq3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                    How can I contact customer support?
                </button>
            </h2>
            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    You can reach us via the <a href="customer.php?page=customer-support">Customer Support</a> page or by email/phone listed in the footer.
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/../../inc/customer/customer-footer.php'; 