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

      if (cineSeleccionado) {
        // Redirige a la página principal (ejercicio1.html o index.html) 
        // pasando el cine seleccionado como parámetro en la URL
        window.location.href = `ejercicio1.html?cine=${cineSeleccionado}`;
      }
    });
  }
});

