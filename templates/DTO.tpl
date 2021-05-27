package com.${project_name}.${models_folder}.dto;

import java.io.Serializable;

/**
* Objeto DTO que representa la tabla '${table_name}'
* @author: Nelson Ariza
* @date: ${date}	 
*/
public class ${singular_class_name}DTO implements Serializable {

    private static final long serialVersionUID = 1L;

${variables}

    public ${singular_class_name}DTO() {
    }

${constructor}
${to_string}
${getters}${setters}}