#---------------------------
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro-cpt.pot, cd to the main /pmpro-cpt/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro-cpt.pot open before running script above)
# then execute `cp languages/pmpro-cpt.pot languages/pmpro-cpt.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-cpt.po --output-file languages/pmpro-cpt.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-cpt.pot... "
xgettext -j -o languages/pmpro-cpt.pot \
--default-domain=pmpro-cpt \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--keyword=esc_html__ \
--keyword=esc_html_e \
--keyword=esc_html_x \
--keyword=esc_attr__ \
--keyword=esc_attr_e \
--keyword=esc_attr_x \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"