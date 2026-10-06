<div id="bookingpress-admin-note-model" v-cloak class="bookingpress-admin-note-dialog-container" >
    <bp-ui-dialog id="note_confirm_modal" class="bpa-dialog bpa-dailog__small bpa-dialog--admin-note" :fullscreen="false" title="" v-model="note_confirm_modal" :close-on-click-modal="true" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display">
        <div class="bpa-dialog-heading">
            <bp-ui-row type="flex">
                <bp-ui-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
                    <h1 class="bpa-page-heading" ><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></h1>
                </bp-ui-col>
            </bp-ui-row>
        </div>
        <div class="bpa-dialog-body">
            <bp-ui-container class="bpa-grid-list-container bpa-add-categpry-container">
                <div class="bpa-form-row">
                    <bp-ui-row>
                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                            <bp-ui-form ref="admin_note_confirm_form" :model="admin_note_confirm_form" label-position="top">
                                <bp-ui-row>
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                        <bp-ui-form-item prop="bookingpress_add_admin_note">
                                            <bp-ui-input class="bpa-form-control" v-model="admin_note_confirm_form.bookingpress_add_admin_note" placeholder="<?php esc_html_e('Add Admin note','bookingpress-appointment-booking') ?>" type="textarea" :rows="6"></bp-ui-input>
                                        </bp-ui-form-item>
                                    </bp-ui-col>								
                                </bp-ui-row>
                            </bp-ui-form>
                        </bp-ui-col>
                    </bp-ui-row>
                </div>
            </bp-ui-container>
        </div>
        <div class="bpa-dialog-footer">
            <div class="bpa-hw-right-btn-group">
                <bp-ui-button class="bpa-btn bpa-btn__small bpa-btn--primary" :class="(is_display_admin_note_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="bookingpress_add_note(admin_note_confirm_form.payment_id,admin_note_confirm_form.appointment_id)" :disabled="is_admin_note_btn_disabled">
                    <span class="bpa-btn__label"><?php esc_html_e( 'Save', 'bookingpress-appointment-booking' ); ?></span>
                    <div class="bpa-btn--loader__circles">				    
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                </bp-ui-button>
            </div>
        </div>
    </bp-ui-dialog>
</div>