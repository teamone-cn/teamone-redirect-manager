    <div class="wrap">
        <h1>
            <?php echo $update ?  esc_html__( 'Edit Redirect Rule', 'safe-redirect-manager' ): _x( 'Create Redirect Rule', 'redirect rule', 'safe-redirect-manager' ); ?>
            <?php if ( $update ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=team-one-redirect-create' ) ?>" class="page-title-action">
                    <?php echo  _x( 'Create Redirect Rule', 'redirect rule', 'safe-redirect-manager' ) ?>
                </a>
            <?php endif; ?>
        </h1>
        <?php

        if ( $update ) :
            $redirect_form_action = admin_url( 'admin.php?page=team-one-redirect-request-handler&id=' . absint( $id ) );
        else :
            $redirect_form_action = admin_url( 'admin.php?page=team-one-redirect-request-handler' );
        endif;
        ?>
        <form method="post" action="<?php echo $redirect_form_action ?>">
            <?php
            if ( $update ) :
                wp_nonce_field( 'update-redirect_' . absint( $id ) );
            else :
                wp_nonce_field( 'create-redirect' );
            endif;
            ?>
            <table class="wp-list-table widefat fixed hfcm-form-width form-table">
                <tr>
                    <th class="hfcm-th-width">Enable Regular Expressions (advanced)</th>
                    <td>
                        <input name="data[redirect_rule_from_regex]"  id="redirect_rule_from_regex" type="checkbox" value="1" <?php checked( true, (bool) $redirect_rule_from_regex ); ?> >
                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">Redirect Name</th>
                    <td>
                        <input type="text" name="data[redirect_name]" value="<?php echo esc_attr( $redirect_name ); ?>"
                               class="hfcm-field-width"/>
                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">Redirect From</th>
                    <td>
                        <input type="text" name="data[redirect_rule_from]" value="<?php echo esc_attr( $redirect_rule_from ); ?>"
                               class="hfcm-field-width"/>
                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">Redirect To</th>
                    <td>
                        <input type="text" name="data[redirect_rule_to]" value="<?php echo esc_attr( $redirect_rule_to ); ?>"
                               class="hfcm-field-width"/>
                    </td>
                </tr>
                <?php
                $nnr_redirect_type_array = array(
                    '301' => "301 Moved Permanently",
                    '3001' => "Url Rewriting"
                ); ?>
                <tr id="redirect_rule_status_code">
                    <th class="hfcm-th-width">
                        Redirect Type
                    </th>
                    <td>
                        <select name="data[redirect_rule_status_code]">
                            <?php
                            foreach ( $nnr_redirect_type_array as $nnr_key => $nnr_item ) {
                                if ( $nnr_key == $redirect_rule_status_code ) {
                                    echo "<option value='" . esc_attr( $nnr_key ) . "' selected>" . esc_html( $nnr_item ) . "</option>";
                                } else {
                                    echo "<option value='" . esc_attr( $nnr_key ) . "'>" . esc_html( $nnr_item ) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">Status</th>
                    <td>
                        <select name="data[status]">
                            <?php
                            foreach ( $nnr_redirect_status_array as $skey => $statusv ) {
                                if ( $status == $skey ) {
                                    echo "<option value='" . esc_attr( $skey ) . "' selected='selected'>" . esc_html( $statusv ) . '</option>';
                                } else {
                                    echo "<option value='" . esc_attr( $skey ) . "'>" . esc_html( $statusv ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">Redirect Desc</th>
                    <td class="nnr-mt-20 ">
                        <textarea  name="data[redirect_rule_notes]"  class="hfcm-field-width " id="redirect_rule_notes" rows="20"><?php echo html_entity_decode( $redirect_rule_notes ); ?></textarea>
                    </td>
                </tr>
            </table>
            <div class="nnr-mt-20">

                <div class="nnr-mt-20 nnr-hfcm-codeeditor-box">
                    <div class="wp-core-ui">
                        <input type="submit"
                               name="<?php echo $update ? 'update' : 'insert'; ?>"
                               value="Save"
                               class="button button-primary button-large nnr-btnsave">
                        <?php if ( $update ) :
                            $delete_nonce = wp_create_nonce( 'team_one_redirect_manager_delete' );
                            ?>
                            <a onclick="return redirect_confirm_delete();"
                               href="<?php echo esc_url( admin_url( 'admin.php?page=th_team_one_redirect_manager&action=delete&_wpnonce=' . $delete_nonce . '&id=' . absint( $id ) ) ); ?>"
                               class="button button-secondary button-large nnr-btndelete">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>