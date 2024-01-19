document.addEventListener('submit', function(event) {

    if (!event.target.matches('form.bayarcash-form')) return;

    event.preventDefault();

    const form = document.querySelector('form.bayarcash-form');

    const buyer_ic_no = form.querySelector('#buyer_ic_no').value;
    const order_no = form.querySelector('#order_no').value;

    const data = {
        "buyer_ic_no": buyer_ic_no,
        "order_no": order_no,
    };

    initTransaction(data);

}, false);


function initTransaction(data) {

    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() { // Call a function when the state changes.
        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            // Request finished. Do processing here.
            if (xhr.responseText === '1') {
                const form = document.querySelector('form.bayarcash-form');
                form.submit();
            };
        }
    }

    xhr.open("POST", 'TransactionController.php', true);

    //Send the proper header information along with the request
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send(`buyer_ic_no=${data.buyer_ic_no}&order_no=${data.order_no}`);

}