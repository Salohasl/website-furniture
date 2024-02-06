const swiperEl = document.querySelector('swiper-container');

    const params = {
      centeredSlides: false,
      slidesPerGroupSkip: 1,
      grabCursor: true,
      keyboard: {
        enabled: true,
      },
      breakpoints: {
        769: {
          slidesPerView: 2,
          slidesPerGroup: 2,
        },
      },
      scrollbar: true,
      navigation: true,
      pagination: {
        clickable: true,
      },
    };

    Object.assign(swiperEl, params)

    swiperEl.initialize();

    
    var swiper = new Swiper(".mySwiper", {
      slidesPerView: 1,
      centeredSlides: false,
      slidesPerGroupSkip: 1,
      grabCursor: true,
      keyboard: {
        enabled: true,
      },
      breakpoints: {
        769: {
          slidesPerView: 2,
          slidesPerGroup: 2,
        },
      },
      scrollbar: {
        el: ".swiper-scrollbar",
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
    });
  /* Для всплывающего окна */ 
  let menuBtn = document.querySelector('.menuBtn');
  let menu = document.querySelector('ul');
  menuBtn.addEventListener('click', () =>{
  menuBtn.classList.toggle('active');
  menu.classList.toggle('popUp');
  });


