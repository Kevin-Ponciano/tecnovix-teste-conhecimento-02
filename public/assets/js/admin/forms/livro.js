crud.field('endereco.cep').onChange(function (field) {
    let cep = field.value.replace('-', '');
    let validacep = /^[0-9]{8}$/;
    if(!validacep.test(cep)) {
            return;
    }
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(function (response) {
            return response.json();
        }).then((data) => {
        if (data.erro) {
            alert('CEP não encontrado')
            return;
        }
        crud.field('endereco.rua').input.value = data.logradouro;
        crud.field('endereco.bairro').input.value = data.bairro;
        crud.field('endereco.cidade').input.value = data.localidade;
        crud.field('endereco.uf').input.value = data.uf;
    }).catch(function (error) {
        alert('Erro ao buscar CEP')
    });
}).change();
