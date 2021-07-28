import React, { Component } from 'react';
import '../css/app.css';
import {Card} from './components/Card/Card';
import {Expo} from './components/Expo/Expo';
import Footer from './components/footer/Footer';


const style = { width:'80%',
                minHeight:'200px',
                margin: '0 auto',
                padding: "20px",
                border: '2px solid black' }


export default class App extends Component {

    constructor(props) {
        super(props);
        this.state = {
            animals: [
                    {name:'cat',color:'black'},
                    {name:'dog',color:'yellow'},
                    {name:'fox',color:'orange'},
                    {name:'lion',color:'gignger'},
                    {name:'zebra',color:'red'}],
            title: "Animals",
            showCards: false,
            showDescription: false
        }
        
    }

    showCardsHandler = () => {
        this.setState({
            showCards: !this.state.showCards
        })
    }

    showDescriptionHandler = () => {
        this.setState({
            showDescription: !this.state.showDescription
        })
    }
    
    inputHandler = (event) => {
        this.setState({
            title: event.target.value
        })
    }


    eventHandler(event){
        
        this.setState({
            title: event.target.value
        })
    }

    render() {
        let animal = this.state.animals;
        let temp = '';
        return (
            <div>
                <div>
                    <Expo></Expo>
                    <h1>{this.state.title}</h1>
                </div>

                {this.state.showCards ? 
                
                <div className = "App" style = {style}>
                    {
                        animal.map((item,index)=>{
                            return(
                                <Card name= {item.name} color = {item.color} click={this.eventHandler.bind(this)} key={index} show = {this.state.showDescription}/>
                            )
                        })
                    }
                </div> : null}
                <div>
                    <button onClick={this.showCardsHandler}>SHOW | HIDE</button>
                    <button onClick={this.showDescriptionHandler}>Description</button>
                </div>
                

            <Footer/>

            </div>
        );
    }
}