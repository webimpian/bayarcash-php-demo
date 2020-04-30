alert('HOHOHOHO');
        /* ------------------
         * Validation example
         * ------------------ */
        jQuery("form#frmPayment input[name='CardNo']").on('keyup', function(e) {
            var cardNo = e.target.value;
            updateCardType(cardNo);
            if (cardNo.length >= 16) {
                $spanEL = jQuery("#errorCardNo");
                if (!luhnCheck(cardNo)) {
                    $spanEL.html('Card no entered is invalid');
                    $spanEL.show();
                } else {
                    $spanEL.html('');
                    $spanEL.hide();
                }
            }
        });
