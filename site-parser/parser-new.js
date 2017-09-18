const osmosis = require('osmosis');
const fs = require('fs');

var savedData = [];
osmosis
   .get('https://evrochehol.ru/catalog/chekhly-na-kresla-ikea/').delay(3000)
   .paginate('.pagination .next a[href]').delay(3000)
   .then(function(context, data) {
        console.log(data)
        osmosis.headers(context.request.headers)
    })
   .follow('.bx_catalog_item_images[href]').delay(3000)
   .find('.product-page').delay(3000)
   .set({
        'title':        '.product-title',
        'price':        '.product-detail-price .title-price:not(.old_price)',
        'description':  '#properties:html',
        'img':          ['.product-illustration .img-responsive@src'],
        'imgPhoto':     ['.product-illustration .img-responsive@src']
    }).delay(3000)

   .data(function(data) {
      var new_price = parseInt(data['price'])*3.5/100;
      data['price'] = new_price.toFixed(2);
      data['imgPhoto'].splice(0, 1);
      savedData.push(data);
   })
   .log(console.log) // включить логи
   .error(console.error) // на случай нахождения  
   .done(function() {
      fs.writeFile('chekhly-na-kresla-ikea.json', JSON.stringify( savedData, null, 4), function(err) {
        console.log('Общее количество позиций: ' + savedData.length);
        if(err) console.error(err);
        else console.log('Data Saved to data.json file');
      })
   });