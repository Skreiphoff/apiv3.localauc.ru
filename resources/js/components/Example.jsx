import React, { Component } from 'react';

class Example2 extends Component {

    constructor(props){
        super(props);

        this.state = {
            error:null,
            isLoaded: false,
            items: []
        }
    }

    componentDidMount() {
        fetch("https://jsonplaceholder.typicode.com/users")
            //.then(res => res.json())
            .then(
                (result) => {
                this.setState({
                    isLoaded: true,
                    items: result.items
                });
                console.log(result);
                },
                // Примечание: важно обрабатывать ошибки именно здесь, а не в блоке catch(),
                // чтобы не перехватывать исключения из ошибок в самих компонентах.
                (error) => {
                this.setState({
                    isLoaded: true,
                    error
                });
                }
            )
        
    }

    render() {
        const { error, isLoaded, items } = this.state;
        if (error) {
          return <div>Ошибка: {error.message}</div>;
        } else if (!isLoaded) {
          return <div>Загрузка...</div>;
        } else {
          return (
            {items}
          );
        }
      }

}



export default class Example extends Component {
    render() {
        return (
            <div className="App">Hello World;</div>
        );
    }
}