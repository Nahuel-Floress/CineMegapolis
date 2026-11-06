const container = document.querySelector(".container");
const btnSignIn = document.getElementById("btn-sign-in");
const btnSignUp = document.getElementById("btn-sign-up");

btnSignIn.addEventListener("click", ()=>{
   container.classList.remove("toggle")
});
btnSignUp.addEventListener("click", ()=>{
     container.classList.add("toggle");
});

document.addEventListener("DOMContentLoaded", function() {
  const selectCine = document.getElementById("selectCine");

  if (selectCine) {
    selectCine.addEventListener("change", function() {
      const cineSeleccionado = this.value;

      if (cineSeleccionado) 
      {

        window.location.href = `inicio.php?cine=${cineSeleccionado}`;
      }
    });
  }
});

