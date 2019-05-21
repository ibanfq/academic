<?php if (empty($ajax_section) && !empty($monitor['Monitor']['show_events']) && !empty($monitor['Monitor']['show_media'])): ?>
    <div id="content-left">
        <?php require('_board.ctp') ?>
    </div>
    <div id="content-right">
        <?php require('_media.ctp') ?>
    </div>
<?php elseif ((empty($ajax_section) || $ajax_section === 'content-board') && !empty($monitor['Monitor']['show_events'])): ?>
    <?php require('_board.ctp') ?>
<?php elseif ((empty($ajax_section) || $ajax_section === 'content-media') && !empty($monitor['Monitor']['show_media'])): ?>
    <?php require('_media.ctp') ?>
<?php endif; ?>
