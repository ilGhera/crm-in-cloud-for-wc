<?php
/**
 * Checkout template file
 *
 * @author ilGhera
 * @package wc-checkout-fields/templates
 * @since 0.9.0
 */

$wcexd_company_invoice = get_option( 'wcexd_company_invoice' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_company_invoice = isset( $_POST['wcexd_company_invoice'] ) ? $_POST['wcexd_company_invoice'] : 0;
    update_option( 'wcexd_company_invoice', $wcexd_company_invoice );
    update_option( 'billing_wcexd_piva_active', $wcexd_company_invoice );
}

$wcexd_private_invoice = get_option( 'wcexd_private_invoice' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_private_invoice = isset( $_POST['wcexd_private_invoice'] ) ? $_POST['wcexd_private_invoice'] : 0;
    update_option( 'wcexd_private_invoice', $wcexd_private_invoice );
}

$wcexd_private = get_option( 'wcexd_private' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_private = isset( $_POST['wcexd_private'] ) ? $_POST['wcexd_private'] : 0;
    update_option( 'wcexd_private', $wcexd_private );
}

/*Aggiorno cf nel db in base alle opzioni precedenti*/
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    if ( $wcexd_company_invoice === 0 && $wcexd_private_invoice === 0 && $wcexd_private === 0 ) {
        update_option( 'billing_wcexd_cf_active', 0 );
    } else {
        update_option( 'billing_wcexd_cf_active', 1 );
    }
}

$wcexd_document_type = get_option( 'wcexd_document_type' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_document_type = isset( $_POST['wcexd_document_type'] ) ? $_POST['wcexd_document_type'] : 0;
    update_option( 'wcexd_document_type', $wcexd_document_type );
}

$wcexd_cf_mandatory = get_option( 'wcexd_cf_mandatory' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_cf_mandatory = isset( $_POST['wcexd_cf_mandatory'] ) ? $_POST['wcexd_cf_mandatory'] : 0;
    update_option( 'wcexd_cf_mandatory', $wcexd_cf_mandatory );
}

$wcexd_fields_check = get_option( 'wcexd_fields_check' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_fields_check = isset( $_POST['wcexd_fields_check'] ) ? $_POST['wcexd_fields_check'] : 0;
    update_option( 'wcexd_fields_check', $wcexd_fields_check );
}

$wcexd_vies_check = get_option( 'wcexd_vies_check' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_vies_check = isset( $_POST['wcexd_vies_check'] ) ? $_POST['wcexd_vies_check'] : 0;
    update_option( 'wcexd_vies_check', $wcexd_vies_check );
}

$wcexd_pec_active = get_option( 'billing_wcexd_pec_active' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_pec_active = isset( $_POST['wcexd_pec_active'] ) ? $_POST['wcexd_pec_active'] : 0;
    update_option( 'billing_wcexd_pec_active', $wcexd_pec_active );
}

$wcexd_pa_code_active = get_option( 'billing_wcexd_pa_code_active' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_pa_code_active = isset( $_POST['wcexd_pa_code_active'] ) ? $_POST['wcexd_pa_code_active'] : 0;
    update_option( 'billing_wcexd_pa_code_active', $wcexd_pa_code_active );
}

$wcexd_piva_only_ue = get_option( 'wcexd_piva_only_ue' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_piva_only_ue = isset( $_POST['wcexd_piva_only_ue'] ) ? $_POST['wcexd_piva_only_ue'] : 0;
    update_option( 'wcexd_piva_only_ue', $wcexd_piva_only_ue );
}

$wcexd_only_italy = get_option( 'wcexd_only_italy' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_only_italy = isset( $_POST['wcexd_only_italy'] ) ? $_POST['wcexd_only_italy'] : 0;
    update_option( 'wcexd_only_italy', $wcexd_only_italy );
}

$wcexd_cf_only_italy = get_option( 'wcexd_cf_only_italy' );
if ( isset( $_POST['wcexd-options-sent'] ) ) {
    $wcexd_cf_only_italy = isset( $_POST['wcexd_cf_only_italy'] ) ? $_POST['wcexd_cf_only_italy'] : 0;
    update_option( 'wcexd_cf_only_italy', $wcexd_cf_only_italy );
}
?>

<div id="wcexd-impostazioni" class="wcexd-admin" style="display: block;">

    <h3 class="wcexd"><?php echo __( 'Pagina di checkout', 'wcexd' ); ?></h3>

    <!--Form Fornitori-->
    <form name="wcexd-options-submit" id="wcexd-options-submit"  method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo __( 'Documenti fiscali', 'wcexd' ); ?></th>
                <td>
                    <p style="margin-bottom: 10px;">
                        <label for="wcexd_company_invoice">
                            <input type="checkbox" name="wcexd_company_invoice" value="1"<?php echo $wcexd_company_invoice == 1 ? ' checked="checked"' : ''; ?>>
                            <?php echo '<span class="tax-document">' .  __( 'Azienda ( Fattura )', 'wcexd' ) . '</span>'; ?>
                        </label>							
                    </p>
                    <p style="margin-bottom: 10px;">
                        <label for="wcexd_private_invoice">
                            <input type="checkbox" name="wcexd_private_invoice" value="1"<?php echo $wcexd_private_invoice == 1 ? ' checked="checked"' : ''; ?>>
                            <?php echo '<span class="tax-document">' .  __( 'Privato ( Fattura )', 'wcexd' ) . '</span>'; ?>
                        </label>
                    </p>
                    <p>
                        <label for="wcexd_private">
                            <input type="checkbox" name="wcexd_private" value="1"<?php echo $wcexd_private == 1 ? ' checked="checked"' : ''; ?>>
                            <?php echo '<span class="tax-document">' .  __( 'Privato ( Ricevuta )', 'wcexd' ) . '</span>'; ?>
                        </label>
                    </p>
                    <p class="description"><?php echo __( 'Attivando uno o più tipi di fattura, verranno visualizzati i campi P.IVA e Codice Fiscale quando necessari', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Tipo documento', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_document_type">
                        <input type="checkbox" name="wcexd_document_type" value="1"<?php echo $wcexd_document_type == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Mostra la scelta del tipo di documento come primo campo', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'CF obbligatorio', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_cf_mandatory">
                        <select class="wcexd" name="wcexd_cf_mandatory">
                            <option value="0"<?php echo 0 == $wcexd_cf_mandatory ? ' selected="selected"' : null;  ?>><?php esc_html_e( 'Mai', 'wcexd' ); ?></option>
                            <option value="1"<?php echo 1 == $wcexd_cf_mandatory ? ' selected="selected"' : null;  ?>><?php esc_html_e( 'Solo Ricevuta', 'wcexd' ); ?></option>
                            <option value="2"<?php echo 2 == $wcexd_cf_mandatory ? ' selected="selected"' : null;  ?>><?php esc_html_e( 'Solo Fattura', 'wcexd' ); ?></option>
                            <option value="3"<?php echo 3 == $wcexd_cf_mandatory ? ' selected="selected"' : null;  ?>><?php esc_html_e( 'Sempre', 'wcexd' ); ?></option>
                        </select>
                    </label>
                    <p class="description"><?php echo __( 'Rendi obbligatorio il campo Codice Fiscale', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Verifica C.F.', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_fields_check">
                        <input type="checkbox" name="wcexd_fields_check" value="1"<?php echo $wcexd_fields_check == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Attiva il controllo di validità del Codice Fiscale', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Verifica VIES', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_vies_check">
                        <input type="checkbox" name="wcexd_vies_check" value="1"<?php echo $wcexd_vies_check == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Attiva il controllo VIES per la Partita IVA <i>( Richiede che SOAP sia attivo sul server )</i>', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'PEC', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_pec_active">
                        <input type="checkbox" name="wcexd_pec_active" value="1"<?php echo $wcexd_pec_active == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Attiva il campo PEC per la fatturazione elettronica', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Codice destinatario', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd-pa-code">
                        <input type="checkbox" name="wcexd_pa_code_active" value="1"<?php echo $wcexd_pa_code_active == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Attiva il campo Codice destinatario per la fatturazione elettronica', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Solo UE', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_piva_only_ue">
                        <input type="checkbox" name="wcexd_piva_only_ue" value="1"<?php echo $wcexd_piva_only_ue == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'P.IVA obbligatoria solo per i paesi dell\'UE', 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __( 'Solo Italia', 'wcexd' ); ?></th>
                <td>
                    <label for="wcexd_only_italy">
                        <input type="checkbox" name="wcexd_only_italy" value="1"<?php echo $wcexd_only_italy == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Mostra PEC e Codice destinatario solo per l\'Italia'
                    , 'wcexd' ); ?></p>
                </td>
            </tr>
            <tr>
                <th></th>
                <td>
                    <label for="wcexd_cf_only_italy">
                        <input type="checkbox" name="wcexd_cf_only_italy" value="1"<?php echo $wcexd_cf_only_italy == 1 ? ' checked="checked"' : ''; ?>>
                    </label>
                    <p class="description"><?php echo __( 'Mostra il campo Codice fiscale solo per l\'Italia'
                    , 'wcexd' ); ?></p>
                </td>
            </tr>


        </table>
        <?php wp_nonce_field( 'wcexd-options-submit', 'wcexd-options-nonce' ); ?>
        <p class="submit">
            <input type="submit" name="wcexd-options-sent" class="button-primary" value="<?php esc_attr_e( 'Salva impostazioni', 'wcexd' ); ?>" />
        </p>
    </form>
</div>
