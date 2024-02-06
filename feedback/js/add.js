function agree(){
  
    const agree = document.getElementById('agree');
    const buttonForm = document.getElementById('buttonForm');
    agree.addEventListener('click', () =>{
        if(!(agree.checked)){
            buttonForm.style.backgroundColor = 'grey';
            buttonForm.setAttribute('disabled', '.');
        }else{
            buttonForm.style.backgroundColor = '#710B0B';
            buttonForm.removeAttribute('disabled', '.');
        }
    })
}agree();