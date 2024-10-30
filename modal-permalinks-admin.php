<?php

if(isset($_POST['submit'])) {
    
    $modalPermalinksBootstrapModal = $_POST['modalPermalinksBootstrapModal'];
    $modalPermalinksModalWidth     = $_POST['modalPermalinksModalWidth'];
    update_option('modalPermalinksBootstrapModal', $modalPermalinksBootstrapModal);
    
    if(is_numeric($modalPermalinksModalWidth))
        update_option('modalPermalinksModalWidth', $modalPermalinksModalWidth);
    else
        $modalPermalinksModalWidth = get_option('modalPermalinksModalWidth');
        
    echo '<div class="updated">'.__('Settings Saved', 'modal_permalinks').'</div>';
    
} else {
    
    $modalPermalinksBootstrapModal = get_option('modalPermalinksBootstrapModal');
    $modalPermalinksModalWidth     = get_option('modalPermalinksModalWidth');
    
}

?>

<div class="wrap">
    <?php echo "<h2>".__('Modal Permalinks Settings', 'modal_Permalinks')."</h2>"; ?>
     
    <form name="modalPermalinksForm" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="modalPermalinksHidden" value="Y">
        <p>
            <?php echo __('Load Bootstrap Modal?', 'modal_Permalinks'); ?>
            <select name="modalPermalinksBootstrapModal">
                <?php if($modalPermalinksBootstrapModal == '1'): ?>
                <option value="1" selected><?php echo __('Yes', 'modal_Permalinks'); ?></option>
                <option value="0"><?php echo __('No', 'modal_Permalinks'); ?></option>
                <?php else: ?>
                <option value="1"><?php echo __('Yes', 'modal_Permalinks'); ?></option>
                <option value="0" selected><?php echo __('No', 'modal_Permalinks'); ?></option>
                <?php endif; ?>
            </select>
            
            <?php echo __('(select no ONLY if bootstrap is already loaded in your theme)', 'modal_Permalinks'); ?>
        </p>
        <p>
            <?php echo __('Modal Window Width:', 'modal_permalinks'); ?>
            <input type="number" name="modalPermalinksModalWidth" value="<?php echo $modalPermalinksModalWidth; ?>" />
            <?php echo __('(in px)', 'modal_permalinks'); ?>
        </p>
        <p>
            <?php echo __('Use the shortcode inside a post or a page [modalPermalinks link="POSTorPAGE_PERMALINK_HERE"]LINK_NAME_HERE[/modalPermalinks]', 'modal_Permalinks'); ?>
        </p>
        <p class="submit">
            <input class="button button-primary" type="submit" name="submit" value="<?php echo __('Save Settings', 'modal_Permalinks') ?>" />
        </p>
    </form>
</div>