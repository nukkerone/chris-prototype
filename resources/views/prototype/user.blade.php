@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">User {{ $user->name }}</div>

                    <div class="row">
                        <div class="col-md-6">

                            <div class="p-3">
                                <h3>Flower information - week {{ $flower->current_week ? $flower->current_week : 0 }}</h3>

                                <ul class="list-group">
                                    <li class="list-group-item">User wallet {{ $user->wallet }}</li>
                                    <li class="list-group-item">User position {{ $position }}</li>
                                </ul>
                            </div>

                            <div id="myVisualTree" style="border: 1px solid black; background:#1F4963; width: 100%; height: 300px"></div>

                        </div>
                        <div class="col-md-6">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let myVisualTree;
        let data = JSON.parse('{!! $tree !!}');
        data = data[0];

        function init() {
            let $go = go.GraphObject.make;

            // Now we can initialize a Diagram that looks at the visual tree that constitutes the Diagram above.
            myVisualTree =
                $go(go.Diagram, "myVisualTree",
                    {
                        initialContentAlignment: go.Spot.Left,
                        initialAutoScale: go.Diagram.Uniform,
                        isReadOnly: true,  // do not allow users to modify or select in this view
                        allowSelect: false,
                        layout: $go(go.TreeLayout, { nodeSpacing: 5 })  // automatically laid out as a tree
                    });

            myVisualTree.nodeTemplate =
                $go(go.Node, "Auto",
                    $go(go.Shape, { fill: "darkkhaki", stroke: null }),  // assume a dark background
                    $go(go.TextBlock,
                        {
                            font: "bold 13px Helvetica, bold Arial, sans-serif",
                            stroke: "black",
                            margin: 3
                        },
                        // bind the text to the Diagram/Layer/Part/GraphObject converted to a string
                        new go.Binding("text", "", function(x) {
                            // if the node represents a link, be sure to include the "to/from" data for that link
                            return x.user.name;
                        }))
                );

            myVisualTree.linkTemplate =
                $go(go.Link,
                    $go(go.Shape, { stroke: "darkkhaki", strokeWidth: 2 })
                );

            drawVisualTree();
        }

        function drawVisualTree() {
            let visualNodeDataArray = [];

            // recursively walk the visual tree, collecting objects as we go
            function traverseVisualTree(obj, parent) {
                obj.vtkey = visualNodeDataArray.length;
                visualNodeDataArray.push(obj);
                if (parent) {
                    obj.parentKey = parent.vtkey;
                }
                obj.children.forEach((child) => {
                    traverseVisualTree(child, obj)
                });
            }

            traverseVisualTree(data, null);

            myVisualTree.model =
                go.GraphObject.make(go.TreeModel,
                    {
                        nodeKeyProperty: "vtkey",
                        nodeParentKeyProperty: "parentKey",
                        nodeDataArray: visualNodeDataArray
                    });
        }

        init();

    </script>
@endpush
