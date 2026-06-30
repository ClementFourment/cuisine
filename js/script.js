
// let book_aperos = Array.from(document.getElementsByClassName('book-aperos'))[0];
// let book_entrees = Array.from(document.getElementsByClassName('book-entrees'))[0];
// let book_plats = Array.from(document.getElementsByClassName('book-plats'))[0];
// let book_desserts = Array.from(document.getElementsByClassName('book-desserts'))[0];

// let parchemin = document.getElementById('parchemin');

// let actual_variables = {'book': '', 'type': '', 'page': ''}

// book_aperos.addEventListener('click', (e) => {
//     parchemin.style.zIndex = "10000";
//     parchemin.classList.add('visible');
//     if (RECETTES_DATA['apero'] && RECETTES_DATA['apero'][1]) {
//         loadEmptyData("Apéros");
//         loadData("Apéros", "apero", 1);
//     }
//     else {
//         loadEmptyData("Apéros", "apero", 1);
//     }
// });
// book_entrees.addEventListener('click', (e) => {
//     parchemin.style.zIndex = "10000";
//     parchemin.classList.add('visible');
//     if (RECETTES_DATA['entree'] && RECETTES_DATA['entree'][1]) {
//         loadEmptyData("Entrées");
//         loadData("Entrées", "entree", 1);
//     }
//     else {
//         loadEmptyData("Entrées", "entree", 1);
//     }
// });
// book_plats.addEventListener('click', (e) => {
//     parchemin.style.zIndex = "10000";
//     parchemin.classList.add('visible');
//     if (RECETTES_DATA['plat'] && RECETTES_DATA['plat'][1]) {
//         loadEmptyData("Plats");
//         loadData("Plats", "plat", 1);
//     }
//     else {
//         loadEmptyData("Plats", "plat", 1);
//     }
// });
// book_desserts.addEventListener('click', (e) => {
//     parchemin.style.zIndex = "10000";
//     parchemin.classList.add('visible');
//     if (RECETTES_DATA['dessert'] && RECETTES_DATA['dessert'][1]) {
//         loadEmptyData("Desserts");
//         loadData("Desserts", "dessert", 1);
//     }
//     else {
//         loadEmptyData("Desserts", "dessert", 1);
//     }
// });

// document.getElementById('close-parchemin').addEventListener('click', (e) => {
//     parchemin.style.zIndex = "-1";
//     parchemin.classList.remove('visible');
// });

    
// document.getElementById('next-page').addEventListener('click', (e) => {
//     loadEmptyData(actual_variables.book, actual_variables.type, actual_variables.page)
//     loadData(actual_variables.book, actual_variables.type, actual_variables.page+1);
// });
// document.getElementById('prev-page').addEventListener('click', (e) => {
//     loadEmptyData(actual_variables.book, actual_variables.type, actual_variables.page)
//     loadData(actual_variables.book, actual_variables.type, actual_variables.page-1);
// });



// document.getElementById('displayAdd').addEventListener('click', (e) => {
//     console.log(actual_variables)
//     document.getElementById('addPage').style.display = "block";
//     var bookId = 0;
//     if (actual_variables.type == "apero"){
//         bookId = 0
//     }
//     if (actual_variables.type == "entree"){
//         bookId = 1
//     }
//     if (actual_variables.type == "plat"){
//         bookId = 2
//     }
//     if (actual_variables.type == "dessert"){
//         bookId = 3
//     }
//     console.log(bookId)
//     document.getElementById('selectBookAddRecette').querySelectorAll('option')[bookId].setAttribute("selected", "selected")
//     // selectBookAddRecette
//     // actual_variables.book
// })



// document.getElementById('addIngredientRecette').addEventListener('click', (e) => {

//     let new_id = 0;
//     let nb_ingredient_inputs = document.getElementById('ingredientListRecette').querySelectorAll('input').length;
//     if (nb_ingredient_inputs != 0) {
//         let last_node_name_ingredient_inputs = document.getElementById('ingredientListRecette').querySelectorAll('input')[nb_ingredient_inputs-1].name;
//         let last_id_ingredient_input = last_node_name_ingredient_inputs.split('_')[1];
//         new_id = parseInt(last_id_ingredient_input)+1; 
//     }
//     let ingredientList = document.getElementById('ingredientListRecette');
//     ingredientList.innerHTML += `<div id="ingredientRecette_${new_id}" class="flex-row"><input type="text" name="ingredient_${new_id}"><span id="del_ingredient_${new_id}" onclick="delIngredient(${new_id})">X</span></div>`;
                

// })


// function delIngredient(id) {
//     document.getElementById('ingredientRecette_'+id).remove();
// }



// function loadData(book, type, page) {
//     const data = RECETTES_DATA[type][page];

//     actual_variables.book = book;
//     actual_variables.type = type;
//     actual_variables.page = page;

//     document.getElementById('titre-parchemin').innerHTML = '<img src="../img/opened-book.png" alt=""><p>'+book+'</p>';
//     document.getElementById('title-recette-parchemin').innerHTML = '<h2><i><u>'+data.nom+'</u></i></h2>';
//     document.getElementById('photo-parchemin').innerHTML = `<img src="../upload/${data.photo}" alt="${data.nom}">`;
    
//     let htmlIng = "<h3>Ingrédients</h3>";
//     htmlIng += "<table>";
//     data.ingredients_qty.forEach((item, i) => {
//             htmlIng += `<td>${item.ingredient}</td><th>${item.qty}</th>`;
//         if (i%2==1) {
//              htmlIng += `<tr>`;
//         }
        
//     });
//     htmlIng += "</table>";
//     document.getElementById('ingredients-parchemin').innerHTML = htmlIng;
    
//     let htmlPrep = "<h3>Préparation</h3><ol>";
//     data.preparation.forEach(step => {
//         htmlPrep += `<li>${step.action}</li>`;
        
//     });
//     htmlPrep += "</table>";
//     document.getElementById('preparation-parchemin').innerHTML = htmlPrep;

//     document.getElementById('difficulte-parchemin').innerHTML = `
//         Préparation: ${data.temps_prep}min | Cuisson: ${data.temps_cuisson}min

//     `;
//     document.getElementById('actual-page').innerHTML = page;

//     if (RECETTES_DATA[type][page+1]) {
//         document.getElementById('next-page').innerHTML = ">";
//     }
//     if (RECETTES_DATA[type][page-1]) {
//         document.getElementById('prev-page').innerHTML = "<";
//     }



// }
// function loadEmptyData(book, type, page) {
//     actual_variables.book = book;
//     actual_variables.type = type;
//     actual_variables.page = page;

//     document.getElementById('title-recette-parchemin').innerHTML = '';
//     document.getElementById('ingredients-parchemin').innerHTML = '';
//     document.getElementById('photo-parchemin').innerHTML = '';
//     document.getElementById('difficulte-parchemin').innerHTML = '';
//     document.getElementById('preparation-parchemin').innerHTML = '';
//     document.getElementById('prev-page').innerHTML = '';
//     document.getElementById('actual-page').innerHTML = '';
//     document.getElementById('next-page').innerHTML = '';

//     document.getElementById('titre-parchemin').innerHTML = '<img src="../img/opened-book.png" alt=""><p>'+book+'</p>';
//     document.getElementById('title-recette-parchemin').innerHTML = '<i>Ce livre ne contient aucune recette.</i>';
// }