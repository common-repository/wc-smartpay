<?php
/**
 * Template for return type top footer
 *
 * @package wc-smartpay
 */

?>
<script>
	(() => {
		// Listen for postMessage events from the iframe
		window.addEventListener('message', function(event) {
			// Check that the message is from a trusted source
			if (!isOriginAllowed(event.origin)) {
				return;
			}

			if (event.data.type === 'redirect_get') {
				window.location.href = event.data.url;
			} else if(event.data.type === 'redirect_post') {
				redirectPost(event.data.url, event.data.data);
			}
		});

		const isOriginAllowed = (origin) => {
			return origin.includes('.smartpay.co.il') || origin.includes('.protected-payment.com');
		}

		const redirectPost = (url, data, target = 'self') => {
			let form = document.createElement('form');
			document.body.appendChild(form);
			form.target = '_' + target;
			form.method = 'post';
			form.action = url;

			Object.entries(data).forEach(entry => {
				const [key, value] = entry;
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = key;
				input.value = value;
				form.appendChild(input);
			});

			form.submit();
			document.body.removeChild(form);
		}
	})();
</script>
