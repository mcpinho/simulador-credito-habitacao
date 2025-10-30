# simulador-credito-habitacao
Exercicio de Simulador Crédito Habitação

- Para instalar o plugin, será necessário colocar a pasta simulador dentro do diretório "\wp-content\plugins" e, posteriormente, ativar o simulador através do menu de Plugins no Back Office do WordPress.
- Para testar o simulador, pode colocar o ficheiro "teste-simulador-api.php" na raiz do projeto ou utilizar o Postman.
- Será necessario configurar o endereço url com o dominio do site "$url = 'endereco-site/wp-json/api/v1/mortgage/calculate';
- Poderá ser necessário configurar as opções de "Ligações permanentes" para aceder à API, através do menu "Opções".
- Foi acrescentada a resposta em anos juntamente com os meses; é agora possível efetuar o cálculo com taxas variáveis.
- Ao chamar o ficheiro "teste-simulador-api.php", este irá criar um ficheiro CSV com os dados das prestações mensais.

NOTA: Informo que, devido à falta de experiência na criação de testes unitários, optei por não incluir essa implementação nesta versão do simulador.
