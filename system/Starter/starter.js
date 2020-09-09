var starterlinks = document.querySelector('.mor-starter-links');

if (starterlinks != null)
{
	var lists = starterlinks.querySelectorAll('li > a');

	[].forEach.call(lists, function(e){
		
		e.addEventListener('click', function(x){
			if (!e.hasAttribute('target'))
			{
				x.preventDefault();
				x.cancelBubble = true;

				e.className = 'active';

				var inner = e.innerText,
					title = document.querySelector('.modal-title'),
					body = document.querySelector('.modal-body > .'+inner.trim().toLowerCase()),
					wrapper = document.querySelector('.modal-wrapper'),
					modal = document.querySelector('.modal'),
					close = modal.querySelector('.modal-close');

				wrapper.style.display = 'flex';

				setTimeout(function(){

					wrapper.style.opacity = 1;

					title.innerText = inner;

					// show modal
					body.style.display = 'block';

					setTimeout(function(){
						modal.style.opacity = 1;
						modal.style.transform = 'translateY(0px)';
					},100);

				},200);

				wrapper.addEventListener('click', closemodal);
				close.addEventListener('click', closemodal);


				function closemodal (xe)
				{
					if (xe.target.className != e.className)
					{
						modal.style.opacity = 0;
						modal.style.transform = 'translateY(100px)';

						e.classList.remove('active');
						
						setTimeout(function(){
							wrapper.style.opacity = 0;
							setTimeout(function(){
								wrapper.style.display = 'none';
								body.style.display = 'none';

							},400);

						},600);
					}
				}
			}
		});
	});
}