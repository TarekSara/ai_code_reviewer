const reviewSchema={
    type: "array",
    items:{
        type:"object",
        required : ["severity","file","issue","suggestion"],
        properties: {
            file : {type: "string"},
            severity:{
                type: "string",
                enum : ["low", "medium", "high"]
            },
            issue : {type :"string"},
            suggestion : {type : "string"},
        }
    }
};