<?php
/**
 * @var \Logiscenter\ImmutableQuote\Block\Customer\QuoteListBlock $block
 * @var \Magento\Framework\Escaper $escaper
 */
?>

<div class="immutable-quotes-container">
    <div class="page-title-wrapper">
        <h1 class="page-title">
            <span class="base"><?= $escaper->escapeHtml(__('My Immutable Quotes')) ?></span>
        </h1>
    </div>

    <?php $quotes = $block->getCustomerImmutableQuotes(); ?>

    <?php if (empty($quotes)): ?>
        <div class="message info empty">
            <div><?= $escaper->escapeHtml(__('You have no immutable quotes yet.')) ?></div>
        </div>
    <?php else: ?>

        <div class="quotes-toolbar">
            <div class="quotes-count">
                <?= $escaper->escapeHtml(__('Total: %1 quote(s)', count($quotes))) ?>
            </div>
        </div>

        <div class="table-wrapper quotes">
            <table class="data table table-quotes-list" id="immutable-quotes-table">
                <caption class="table-caption"><?= $escaper->escapeHtml(__('Immutable Quotes')) ?></caption>
                <thead>
                    <tr>
                        <th scope="col" class="col quote-id"><?= $escaper->escapeHtml(__('Quote #')) ?></th>
                        <th scope="col" class="col created-date"><?= $escaper->escapeHtml(__('Created')) ?></th>
                        <th scope="col" class="col status"><?= $escaper->escapeHtml(__('Status')) ?></th>
                        <th scope="col" class="col items"><?= $escaper->escapeHtml(__('Items')) ?></th>
                        <th scope="col" class="col total"><?= $escaper->escapeHtml(__('Total')) ?></th>
                        <th scope="col" class="col expires"><?= $escaper->escapeHtml(__('Expires')) ?></th>
                        <th scope="col" class="col actions"><?= $escaper->escapeHtml(__('Actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotes as $quote): ?>
                        <?php $quoteData = $block->getQuoteData($quote->getQuoteId()); ?>
                        <tr class="quote-row <?= $quote->isEnabled() ? 'quote-enabled' : 'quote-disabled' ?> <?= $quote->isExpired() ? 'quote-expired' : '' ?>">

                            <!-- Quote ID -->
                            <td class="col quote-id" data-th="<?= $escaper->escapeHtmlAttr(__('Quote #')) ?>">
                                <strong class="quote-number">#<?= $escaper->escapeHtml($quote->getQuoteId()) ?></strong>
                            </td>

                            <!-- Created Date -->
                            <td class="col created-date" data-th="<?= $escaper->escapeHtmlAttr(__('Created')) ?>">
                                <?= $escaper->escapeHtml($block->formatDate($quote->getCreatedAt())) ?>
                            </td>

                            <!-- Status -->
                            <td class="col status" data-th="<?= $escaper->escapeHtmlAttr(__('Status')) ?>">
                                <span class="quote-status <?= $block->getStatusClass($quote) ?>">
                                    <?= $escaper->escapeHtml($block->getStatusLabel($quote)) ?>
                                </span>
                                <?php if ($quote->isExpired()): ?>
                                    <span class="quote-expired-label"><?= $escaper->escapeHtml(__('Expired')) ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Items Count -->
                            <td class="col items" data-th="<?= $escaper->escapeHtmlAttr(__('Items')) ?>">
                                <?= $escaper->escapeHtml(sprintf('%d item(s)', $quoteData['items_count'] ?? 0)) ?>
                            </td>

                            <!-- Total -->
                            <td class="col total" data-th="<?= $escaper->escapeHtmlAttr(__('Total')) ?>">
                                <span class="price">
                                    <?= $escaper->escapeHtml($block->formatPrice($quoteData['grand_total'] ?? 0)) ?>
                                </span>
                            </td>

                            <!-- Expires -->
                            <td class="col expires" data-th="<?= $escaper->escapeHtmlAttr(__('Expires')) ?>">
                                <?php if ($quote->getExpiresAt()): ?>
                                    <?= $escaper->escapeHtml($block->formatDate($quote->getExpiresAt())) ?>
                                <?php else: ?>
                                    <span class="no-expiry"><?= $escaper->escapeHtml(__('Never')) ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="col actions" data-th="<?= $escaper->escapeHtmlAttr(__('Actions')) ?>">
                                <div class="actions-group">

                                    <!-- View Action -->
                                    <a href="<?= $escaper->escapeUrl($block->getViewUrl($quote->getQuoteId())) ?>"
                                       class="action view"
                                       title="<?= $escaper->escapeHtmlAttr(__('View Quote Details')) ?>">
                                        <span><?= $escaper->escapeHtml(__('View')) ?></span>
                                    </a>

                                    <!-- Enable Action -->
                                    <?php if (!$quote->isEnabled() && !$quote->isExpired()): ?>
                                        <button type="button"
                                                class="action enable-quote"
                                                data-quote-id="<?= $escaper->escapeHtmlAttr($quote->getQuoteId()) ?>"
                                                title="<?= $escaper->escapeHtmlAttr(__('Enable Quote')) ?>">
                                            <span><?= $escaper->escapeHtml(__('Enable')) ?></span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Checkout Action -->
                                    <?php if ($quote->isEnabled() && !$quote->isExpired()): ?>
                                        <a href="<?= $escaper->escapeUrl($block->getCheckoutUrl($quote->getQuoteId())) ?>"
                                           class="action checkout primary"
                                           title="<?= $escaper->escapeHtmlAttr(__('Proceed to Checkout')) ?>">
                                            <span><?= $escaper->escapeHtml(__('Checkout')) ?></span>
                                        </a>
                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="text/x-magento-init">
{
    "#immutable-quotes-table": {
        "Logiscenter_ImmutableQuote/js/quotes-list": {
            "enableUrl": "<?= $escaper->escapeJs($escaper->escapeUrl($block->getEnableUrl())) ?>"
        }
    }
}
</script>
