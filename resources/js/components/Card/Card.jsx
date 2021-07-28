import React, { Component } from 'react';
import './card.css'
import PropTypes from 'prop-types';

export const Card = (props) =>{

    if (props.show){
        return (
            <div className = "card" onClick={()=>props.click(props.name)}>

                <h2 >Name: {props.name}</h2>
                <h2>Color: {props.color}</h2>
                <h2>Description: </h2>

            </div>
        )
    }else{
        return(
            <div className = "card" onClick={()=>props.click(props.name)}>
    
                    <h2 >Name: {props.name}</h2>
                    <h2>Color: {props.color}</h2>
    
                </div>
        )
    }

}

Card.propTypes = {
    show: PropTypes.bool,
    name: PropTypes.number,
    color: PropTypes.string
}