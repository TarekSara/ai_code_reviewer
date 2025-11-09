async function testAPI() {
    const codesn=`
    def processdata(data):
        print(data)
    `;
    try{
        const resp=await fetch("http://localhost/ai_code_reviewer/review.php",{
            method: "POST",
            headers : {"Content-Type":"application/json" },
            body:JSON.stringify({
                code: codesn,
                file: "sample.py"
            })
        });
        const result= await resp.json();
        console.log("API Response: ",result);
        if (!Array.isArray(result)){
            console.error("Error : Response is not an array");
        }
        let flag=true;
        result.forEach((item,index) => {
            const required= ["severity","file","issue","suggestion"];
            required.forEach((field) =>{
                if (!(field in item)){
                    console.error(`Item ${index +1} is missing field : ${field}`);
                    flag=false;
                }
            }
        );
        const allowedSever=["low","medium","high"];
        if (!allowedSever.includes(item.severity)){
            console.warn(`Item ${index+1} has invalid severity: ${item.severity}`);
        }
        });
        if (flag){
            console.log("All items have the correct structure and necessary fields");
        }
        else{
            console.log("Some items require missing fields");
        }
    }
    catch (error){
        console.error("Fetch process failed: ", error);
    }
}
testAPI();