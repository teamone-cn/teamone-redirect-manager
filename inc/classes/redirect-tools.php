
<div class="wrap">
    <h1>
        Tools
    </h1>
    <div class="hfcm-meta-box-wrap hfcm-grid">
        <div id="normal-sortables" class="meta-box-sortables">
            <div id="hfcm-admin-tool-export" class="postbox ">
                <div class="postbox-header">
                    <h2 class="hndle">
                    Export Redirect
                    </h2>
                </div>
                <div class="inside">
                    <form method="post">
                        <p>
                            Select the redirect you would like to export and then select your export method. Use the
                            download button to export to a .json file which you can then import to another TeamoneRedirect
                            installation
                        </p>
                        <div class="hfcm-notice notice-warning">
                            <p>NOTE: Import/Export Functionality is only intended to operate within the same website.  Using the export/import to move redirect from one website to a different site, may result in inconsistent behavior, particularly if you have specific elements as criteria such as post、page</p>
                        </div>
                        <div class="hfcm-fields">
                            <div class="hfcm-field hfcm-field-checkbox" data-name="keys" data-type="checkbox">
                                <div class="hfcm-label">
                                    <label for="keys">
                                    Select Redirect
                                    </label>
                                </div>
                                <div class="hfcm-input">
                                    <input type="hidden" name="keys">
                                    
                                        <?php if (!empty($nnr_redirect_data) ) {
                                             ?>
                                            <ul class="hfcm-checkbox-list hfcm-bl">
                                                <li>
                                                <label>
                                                    <input type="checkbox"  id="re-allcheck">全选
                                                </label>
                                                </li>
                                            </ul>
                                            <ul class="hfcm-checkbox-list hfcm-bl">
                                             <?
                                                foreach ( $nnr_redirect_data as $nnr_key => $nnr_value ) {
                                            ?>
                                                <li>
                                                    <label>
                                                        <input type="checkbox"
                                                               id="keys-redirect_<?php echo absint($nnr_value->id); ?>"
                                                               name="nnr_value[]"
                                                               value="redirect_<?php echo absint($nnr_value->id); ?>"> <?php echo esc_html($nnr_value->redirect_name); ?>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                        } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <p class="hfcm-submit">
                            <button type="submit" name="action" class="button button-primary" value="team_one_redirect_download">
                            Export File
                            </button>
                        </p>
                        <?php wp_nonce_field('redirect-nonce'); ?>
                    </form>
                </div>
            </div>
            <div id="hfcm-admin-tool-import" class="postbox ">
                <div class="postbox-header">
                    <h2 class="hndle">
                       Import Redirect
                    </h2>
                </div>
                <div class="inside">
                    <form method="post" enctype="multipart/form-data">
                        <p>
                            Select the TeamoneRedirect JSON file you would like to import. When you click the import button below,
                            TeamoneRedirect will import the field groups.
                        </p>
                        <div class="hfcm-fields">
                            <div class="hfcm-field hfcm-field-file" data-name="redirect_import_file" data-type="file">
                                <div class="hfcm-label">
                                    <label for="hfcm_import_file">
                                        Select File
                                    </label>
                                </div>
                                <div class="hfcm-input">
                                    <div class="hfcm-file-uploader" data-library="all" data-mime_types=""
                                         data-uploader="basic">
                                        <div class="hide-if-value">
                                            <label class="hfcm-basic-uploader">
                                                <input type="file" name="team_one_nnr_redirect_import_file"
                                                       id="team_one_nnr_redirect_import_file">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="hfcm-submit">
                            <input type="submit" class="button button-primary" value="Import">
                        </p>
                        <?php wp_nonce_field('redirect-nonce'); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
