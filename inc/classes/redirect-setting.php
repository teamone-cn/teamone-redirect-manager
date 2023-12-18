<?php 

?>

<div class="wrap">
    <h1>Team One Redirect 设置</h1>
    <span>配置Redirect的功能</span>
        <?php 
            $hfcm_form_action = admin_url( 'admin.php?page=team-one-redirect-set-request' );

            
        ?>
            <form method="post" action="<?php echo $hfcm_form_action ?>">
            <?php 
            if ( $update ) :
                wp_nonce_field( 'update-set-redirect_' . absint( $id ) );
            else :
                wp_nonce_field( 'create-set-redirect' );
            endif;
            ?>
            <input type="hidden" name="id" value="<?php echo absint( $id )?>">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th class="hfcm-th-width">
                               路由规则域名
                            </th>
                            <td>
                                <input type="text" name="data[rule_domain]" value="<?php echo esc_attr( $rule_domain ); ?>" class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th class="hfcm-th-width">
                                Redis 服务器的IP或主机名 Host:
                            </th>
                            <td>
                                <input type="text" name="data[host_txt]" value="<?php echo esc_attr( $host_txt); ?>" class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th class="hfcm-th-width">
                                Redis 端口 Port:
                            </th>
                            <td>
                                <input type="text" name="data[redis_port]" value="<?php echo esc_attr( $redis_port ); ?>"
                                    class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th class="hfcm-th-width">
                                Redis 密码 PassWord:
                            </th>
                            <td>
                                <input type="text" name="data[redis_password]" value="<?php echo esc_attr( $redis_password ); ?>"
                                    class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th class="hfcm-th-width">
                                Redis 域名缓存 KEY:
                            </th>
                            <td>
                                <input type="text" name="data[redis_domain_key]" value="<?php echo esc_attr( $redis_domain_key ); ?>"
                                    class="regular-text"/>
                            </td>
                        </tr>
                        <?php
                        $redirect_type_array = array(
                            '1' => "Wordpress Module",
                            '2' => "Ngnix Module"
                        ); ?>
                        <tr id="redirect_rule_status_code">
                            <th class="hfcm-th-width">
                                重定向模式：
                            </th>
                            <td>
                                <select name="data[redirect_module]">
                                    <?php
                                    foreach ( $redirect_type_array as $redirect_key => $redirect_item ) {
                                        if ( $redirect_key == $redirect_module ) {
                                            echo "<option value='" . esc_attr( $redirect_key ) . "' selected>" . esc_html( $redirect_item ) . "</option>";
                                        } else {
                                            echo "<option value='" . esc_attr( $redirect_key ) . "'>" . esc_html( $redirect_item ) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th class="hfcm-th-width">
                                Ngnix Module配置文件地址:
                            </th>
                            <td>
                                <input type="text" name="data[remodule_file_path]" value="<?php echo esc_attr( $remodule_file_path ); ?>"
                                    class="regular-text"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="nnr-mt-20">
                    <div class="nnr-mt-20 nnr-hfcm-codeeditor-box">
                        <div class="wp-core-ui">
                            <input type="submit"
                            name="<?php echo $update ? 'update' : 'insert'; ?>"
                                value="保存更改"
                                class="button button-primary button-large nnr-btnsave">
                        </div>
                    </div>
                </div>
            </form>
</div>